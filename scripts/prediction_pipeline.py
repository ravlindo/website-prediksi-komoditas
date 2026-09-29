#!/usr/bin/env python
"""Headless time-series training and prediction pipeline for Laravel.

The script intentionally has no database credentials. Laravel exports a clean
CSV snapshot, invokes this process in a queue, validates its outputs, and then
imports the results transactionally.
"""

from __future__ import annotations

import argparse
import json
import math
import sys
import time
import warnings
from datetime import datetime
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.ensemble import HistGradientBoostingRegressor, RandomForestRegressor

try:
    from xgboost import XGBRegressor
except Exception:  # pragma: no cover - reported in candidate audit
    XGBRegressor = None

try:
    from statsmodels.tsa.arima.model import ARIMA
    from statsmodels.tsa.holtwinters import ExponentialSmoothing
    from statsmodels.tsa.statespace.sarimax import SARIMAX
except Exception:  # pragma: no cover - reported in candidate audit
    ARIMA = ExponentialSmoothing = SARIMAX = None

warnings.filterwarnings("ignore")

HORIZONS = [1, 3, 7, 14, 30]
LAGS = [1, 2, 3, 7, 14, 30]
ROLLING_WINDOWS = [3, 7, 14, 30]
MODEL_CATALOG = [
    "Naive",
    "MovingAverage7",
    "MovingAverage14",
    "ExponentialSmoothing",
    "RandomForest",
    "HistGradientBoosting",
    "XGBoost",
    "ARIMA",
    "SARIMA",
]
ML_MODELS = {"RandomForest", "HistGradientBoosting", "XGBoost"}
STAT_MODELS = {"ExponentialSmoothing", "ARIMA", "SARIMA"}
FEATURE_COLUMNS = ["last_observed_price"] + [f"lag_{lag}" for lag in LAGS]
for window in ROLLING_WINDOWS:
    FEATURE_COLUMNS += [f"rolling_mean_{window}", f"rolling_std_{window}"]
FEATURE_COLUMNS += ["day_of_week", "day_of_month", "month", "day_of_year", "week_of_year", "is_weekend"]


def arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Train, evaluate, and forecast commodity prices")
    parser.add_argument("--input", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--cutoff", required=True)
    parser.add_argument("--version", required=True)
    parser.add_argument("--min-history", type=int, default=120)
    parser.add_argument("--min-test", type=int, default=14)
    parser.add_argument("--max-missing", type=float, default=0.70)
    parser.add_argument("--max-mape", type=float, default=15.0)
    return parser.parse_args()


def finite_number(value) -> bool:
    try:
        return math.isfinite(float(value))
    except (TypeError, ValueError):
        return False


def metrics(actual, predicted) -> dict:
    actual = np.asarray(actual, dtype=float)
    predicted = np.asarray(predicted, dtype=float)
    mask = np.isfinite(actual) & np.isfinite(predicted) & (actual > 0)
    actual, predicted = actual[mask], predicted[mask]
    if len(actual) == 0:
        return {"n": 0, "mae": np.nan, "rmse": np.nan, "mape": np.nan, "smape": np.nan}
    error = actual - predicted
    mae = float(np.mean(np.abs(error)))
    rmse = float(np.sqrt(np.mean(np.square(error))))
    mape = float(np.mean(np.abs(error / actual)) * 100)
    denominator = np.abs(actual) + np.abs(predicted)
    smape = float(np.mean(np.where(denominator == 0, 0, 2 * np.abs(error) / denominator)) * 100)
    return {"n": int(len(actual)), "mae": mae, "rmse": rmse, "mape": mape, "smape": smape}


def load_dataset(path: Path, cutoff: pd.Timestamp) -> tuple[pd.DataFrame, pd.DataFrame, pd.DatetimeIndex]:
    source = pd.read_csv(path, encoding="utf-8-sig")
    required = {"tanggal", "pasar", "kategori", "komoditas", "satuan", "harga"}
    missing = required.difference(source.columns)
    if missing:
        raise ValueError("Kolom dataset tidak lengkap: " + ", ".join(sorted(missing)))
    source["tanggal"] = pd.to_datetime(source["tanggal"], errors="coerce")
    source["harga"] = pd.to_numeric(source["harga"], errors="coerce")
    source = source[source["tanggal"].notna() & (source["tanggal"] <= cutoff)].copy()
    source["harga_valid"] = source["harga"].where(source["harga"] > 0)
    if source.empty:
        raise ValueError("Dataset kosong setelah tanggal batas diterapkan.")
    if source["tanggal"].max().normalize() != cutoff.normalize():
        raise ValueError(f"Tanggal terakhir dataset {source['tanggal'].max():%Y-%m-%d} tidak sama dengan cutoff {cutoff:%Y-%m-%d}.")

    metadata = (
        source.sort_values("tanggal")
        .groupby("komoditas", as_index=False)
        .agg(kategori=("kategori", "last"), satuan=("satuan", "last"))
    )
    daily = (
        source.groupby(["tanggal", "komoditas"], as_index=False)
        .agg(harga=("harga_valid", "mean"), jumlah_pasar_tersedia=("harga_valid", "count"))
    )
    calendar = pd.date_range(source["tanggal"].min().normalize(), cutoff.normalize(), freq="D")
    return daily, metadata, calendar


def series_for(daily: pd.DataFrame, commodity: str, calendar: pd.DatetimeIndex) -> pd.Series:
    subset = daily[daily["komoditas"] == commodity].set_index("tanggal")["harga"]
    return subset.reindex(calendar).astype(float).rename("harga")


def quality_profile(series: pd.Series, minimum_history: int, maximum_missing: float) -> dict:
    valid = series.dropna()
    count = int(valid.size)
    missing_rate = float(1 - count / len(series)) if len(series) else 1.0
    unique = int(valid.nunique())
    mean = float(valid.mean()) if count else np.nan
    std = float(valid.std()) if count > 1 else np.nan
    cv = std / mean if finite_number(std) and finite_number(mean) and mean != 0 else np.nan
    if count == 0:
        kind = "TIDAK ADA DATA VALID"
    elif count < minimum_history:
        kind = "DATA TIDAK MENCUKUPI"
    elif missing_rate > maximum_missing:
        kind = "MISSING TERLALU TINGGI"
    elif unique <= 1:
        kind = "KONSTAN"
    elif finite_number(cv) and cv <= 0.005:
        kind = "HAMPIR KONSTAN"
    else:
        kind = "FLUKTUATIF"
    return {
        "harga_valid": count,
        "missing_rate": missing_rate,
        "unique_price": unique,
        "cv": cv,
        "tipe_seri": kind,
    }


def features(series: pd.Series, horizon: int) -> pd.DataFrame:
    frame = pd.DataFrame({"tanggal": series.index, "harga": series.values}).sort_values("tanggal")
    frame["last_observed_price"] = frame["harga"].ffill(limit=7)
    frame["baseline_ma_7"] = frame["last_observed_price"].rolling(7, min_periods=1).mean()
    frame["baseline_ma_14"] = frame["last_observed_price"].rolling(14, min_periods=1).mean()
    for lag in LAGS:
        frame[f"lag_{lag}"] = frame["last_observed_price"].shift(lag)
    historical = frame["last_observed_price"].shift(1)
    for window in ROLLING_WINDOWS:
        frame[f"rolling_mean_{window}"] = historical.rolling(window, min_periods=1).mean()
        frame[f"rolling_std_{window}"] = historical.rolling(window, min_periods=2).std()
    frame["day_of_week"] = frame["tanggal"].dt.dayofweek
    frame["day_of_month"] = frame["tanggal"].dt.day
    frame["month"] = frame["tanggal"].dt.month
    frame["day_of_year"] = frame["tanggal"].dt.dayofyear
    frame["week_of_year"] = frame["tanggal"].dt.isocalendar().week.astype(int)
    frame["is_weekend"] = frame["day_of_week"].isin([5, 6]).astype(int)
    frame["target"] = frame["harga"].shift(-horizon)
    frame["target_date"] = frame["tanggal"] + pd.to_timedelta(horizon, unit="D")
    return frame


def build_ml(name: str):
    if name == "RandomForest":
        return RandomForestRegressor(
            n_estimators=120, max_depth=8, min_samples_leaf=2,
            random_state=42, n_jobs=1,
        )
    if name == "HistGradientBoosting":
        return HistGradientBoostingRegressor(
            max_iter=140, learning_rate=0.05, max_leaf_nodes=15,
            l2_regularization=0.1, random_state=42,
        )
    if name == "XGBoost" and XGBRegressor is not None:
        return XGBRegressor(
            n_estimators=140, learning_rate=0.05, max_depth=4,
            subsample=0.9, colsample_bytree=0.9,
            objective="reg:squarederror", random_state=42, n_jobs=1,
        )
    raise RuntimeError(f"Dependensi {name} tidak tersedia")


def fit_statistical(history: pd.Series, name: str):
    values = history.ffill(limit=7).dropna().astype(float)
    if len(values) < 30:
        raise RuntimeError("Riwayat statistik kurang dari 30 observasi")
    if name == "ExponentialSmoothing" and ExponentialSmoothing is not None:
        return ExponentialSmoothing(values, trend="add", seasonal=None, initialization_method="estimated").fit(optimized=True)
    if name == "ARIMA" and ARIMA is not None:
        return ARIMA(values, order=(1, 1, 1)).fit()
    if name == "SARIMA" and SARIMAX is not None:
        return SARIMAX(
            values, order=(1, 0, 0), seasonal_order=(1, 0, 0, 7),
            enforce_stationarity=False, enforce_invertibility=False,
        ).fit(disp=False, maxiter=60)
    raise RuntimeError(f"Dependensi {name} tidak tersedia")


def statistical_block_prediction(series: pd.Series, rows: pd.DataFrame, name: str) -> np.ndarray:
    if rows.empty:
        return np.array([], dtype=float)
    origin = pd.Timestamp(rows["tanggal"].min())
    history = series.loc[series.index <= origin]
    fitted = fit_statistical(history, name)
    steps = (pd.to_datetime(rows["target_date"]) - origin).dt.days.astype(int)
    maximum = int(steps.max())
    forecast = np.asarray(fitted.forecast(steps=maximum), dtype=float)
    return np.asarray([forecast[step - 1] if step > 0 else np.nan for step in steps], dtype=float)


def baseline_prediction(rows: pd.DataFrame, name: str) -> np.ndarray:
    column = {"Naive": "last_observed_price", "MovingAverage7": "baseline_ma_7", "MovingAverage14": "baseline_ma_14"}[name]
    return rows[column].to_numpy(dtype=float)


def model_prediction(name: str, train: pd.DataFrame, evaluation: pd.DataFrame, series: pd.Series) -> np.ndarray:
    if name in {"Naive", "MovingAverage7", "MovingAverage14"}:
        return baseline_prediction(evaluation, name)
    if name in ML_MODELS:
        model = build_ml(name)
        model.fit(train[FEATURE_COLUMNS], train["target"])
        return np.asarray(model.predict(evaluation[FEATURE_COLUMNS]), dtype=float)
    return statistical_block_prediction(series, evaluation, name)


def validation_folds(rows: pd.DataFrame, minimum_test: int) -> tuple[list[tuple[pd.DataFrame, pd.DataFrame]], pd.DataFrame, pd.DataFrame]:
    test_size = max(14, minimum_test)
    fold_size = 14
    required = test_size + (fold_size * 2) + 60
    if len(rows) < required:
        return [], pd.DataFrame(), pd.DataFrame()
    test = rows.iloc[-test_size:].copy()
    before_test = rows.iloc[:-test_size].copy()
    test_origin = pd.Timestamp(test["tanggal"].min())
    pretest = before_test[pd.to_datetime(before_test["target_date"]) <= test_origin].copy()
    folds = []
    for offset in [2, 1]:
        end = len(before_test) - fold_size * (offset - 1)
        start = end - fold_size
        validation = before_test.iloc[start:end].copy()
        validation_origin = pd.Timestamp(validation["tanggal"].min())
        train = before_test.iloc[:start].copy()
        train = train[pd.to_datetime(train["target_date"]) <= validation_origin].copy()
        if len(train) >= 60 and len(validation) == fold_size:
            folds.append((train, validation))
    return folds, pretest, test


def evaluate_candidates(series: pd.Series, frame: pd.DataFrame, minimum_test: int) -> tuple[pd.DataFrame, dict | None, pd.DataFrame, np.ndarray]:
    rows = frame.dropna(subset=FEATURE_COLUMNS + ["target"]).reset_index(drop=True)
    folds, pretest, test = validation_folds(rows, minimum_test)
    if not folds:
        return pd.DataFrame(), None, pd.DataFrame(), np.array([])

    comparison = []
    for name in MODEL_CATALOG:
        actual_parts, prediction_parts, error = [], [], None
        started = time.monotonic()
        try:
            for train, validation in folds:
                predicted = model_prediction(name, train, validation, series)
                actual_parts.extend(validation["target"].to_numpy(dtype=float))
                prediction_parts.extend(predicted)
            result = metrics(actual_parts, prediction_parts)
        except Exception as exc:
            result = metrics([], [])
            error = str(exc)[:300]
        comparison.append({
            "nama_model": name,
            "validation_n": result["n"],
            "validation_mae": result["mae"],
            "validation_rmse": result["rmse"],
            "validation_mape": result["mape"],
            "validation_smape": result["smape"],
            "jumlah_fold_valid": len(folds) if result["n"] else 0,
            "runtime_detik": round(time.monotonic() - started, 3),
            "error": error,
        })

    comparison_df = pd.DataFrame(comparison)
    valid = comparison_df[
        comparison_df["validation_mae"].notna()
        & np.isfinite(comparison_df["validation_mae"])
        & (comparison_df["validation_n"] >= 14)
    ].sort_values(["validation_mae", "validation_smape", "nama_model"])
    if valid.empty:
        return comparison_df, None, test, np.array([])
    selected = valid.iloc[0].to_dict()
    predicted_test = model_prediction(str(selected["nama_model"]), pretest, test, series)
    return comparison_df, selected, test, predicted_test


def forecast(series: pd.Series, frame: pd.DataFrame, horizon: int, name: str) -> float:
    latest = frame.iloc[[-1]]
    if name in {"Naive", "MovingAverage7", "MovingAverage14"}:
        return float(baseline_prediction(latest, name)[0])
    if name in ML_MODELS:
        training = frame.dropna(subset=FEATURE_COLUMNS + ["target"])
        if training.empty or latest[FEATURE_COLUMNS].isna().any(axis=None):
            return np.nan
        model = build_ml(name)
        model.fit(training[FEATURE_COLUMNS], training["target"])
        return float(model.predict(latest[FEATURE_COLUMNS])[0])
    fitted = fit_statistical(series, name)
    return float(np.asarray(fitted.forecast(steps=horizon), dtype=float)[-1])


def interval(prediction: float, actual: np.ndarray, predicted: np.ndarray) -> tuple[float, float]:
    mask = np.isfinite(actual) & np.isfinite(predicted)
    residual = actual[mask] - predicted[mask]
    if len(residual) >= 5:
        lower_error, upper_error = np.quantile(residual, [0.025, 0.975])
    elif len(residual):
        spread = float(np.std(residual)) * 1.96
        lower_error, upper_error = -spread, spread
    else:
        spread = max(prediction * 0.05, 1.0)
        lower_error, upper_error = -spread, spread
    lower = max(0.0, min(prediction, prediction + float(lower_error)))
    upper = max(prediction, prediction + float(upper_error))
    return lower, upper


def safe_json_records(frame: pd.DataFrame) -> list[dict]:
    clean = frame.replace({np.nan: None, np.inf: None, -np.inf: None})
    return clean.to_dict(orient="records")


def main() -> int:
    args = arguments()
    input_path = Path(args.input).resolve()
    output_dir = Path(args.output).resolve()
    output_dir.mkdir(parents=True, exist_ok=True)
    cutoff = pd.Timestamp(args.cutoff).normalize()
    started = time.monotonic()
    generated_at = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    print("[1/5] Membaca snapshot MySQL", flush=True)
    daily, metadata, calendar = load_dataset(input_path, cutoff)
    commodities = sorted(metadata["komoditas"].astype(str).unique())
    profiles, predictions, evaluations, all_comparisons, selected_parameters = [], [], [], [], []

    print(f"[2/5] Membentuk profil kualitas {len(commodities)} komoditas", flush=True)
    quality_by_name = {}
    for commodity in commodities:
        series = series_for(daily, commodity, calendar)
        profile = quality_profile(series, args.min_history, args.max_missing)
        profiles.append({"komoditas": commodity, **profile})
        quality_by_name[commodity] = profile

    print("[3/5] Walk-forward selection dan final holdout", flush=True)
    for index, commodity in enumerate(commodities, 1):
        profile = quality_by_name[commodity]
        if profile["tipe_seri"] in {"TIDAK ADA DATA VALID", "DATA TIDAK MENCUKUPI", "MISSING TERLALU TINGGI"}:
            continue
        series = series_for(daily, commodity, calendar)
        for horizon in HORIZONS:
            frame = features(series, horizon)
            comparison, selected, test, predicted_test = evaluate_candidates(series, frame, args.min_test)
            if not comparison.empty:
                comparison.insert(0, "horizon_hari", horizon)
                comparison.insert(0, "komoditas", commodity)
                all_comparisons.extend(comparison.to_dict(orient="records"))
            if selected is None or test.empty:
                continue
            test_metrics = metrics(test["target"], predicted_test)
            naive_predicted = baseline_prediction(test, "Naive")
            naive_metrics = metrics(test["target"], naive_predicted)
            beats_naive = finite_number(test_metrics["mae"]) and finite_number(naive_metrics["mae"]) and test_metrics["mae"] <= naive_metrics["mae"] + 1e-9
            enough = test_metrics["n"] >= args.min_test
            status = "LAYAK" if enough and test_metrics["mape"] <= args.max_mape and beats_naive else "BELUM LAYAK"
            model_name = str(selected["nama_model"])
            future = forecast(series, frame, horizon, model_name)
            if not finite_number(future):
                continue
            future = max(0.0, float(future))
            lower, upper = interval(future, test["target"].to_numpy(dtype=float), predicted_test)
            target_date = cutoff + pd.Timedelta(days=horizon)
            predictions.append({
                "komoditas": commodity,
                "pasar": "Kabupaten Mojokerto",
                "tanggal_prediksi": target_date.strftime("%Y-%m-%d"),
                "horizon_hari": horizon,
                "harga_prediksi": future,
                "batas_bawah": lower,
                "batas_atas": upper,
                "nama_model": model_name,
                "mae": test_metrics["mae"],
                "rmse": test_metrics["rmse"],
                "mape": test_metrics["mape"],
                "smape": test_metrics["smape"],
                "tanggal_model_dibuat": generated_at,
                "status_layak": status,
            })
            evaluations.append({
                "pasar": "Kabupaten Mojokerto",
                "komoditas": commodity,
                "horizon_hari": horizon,
                "model_terpilih_validation": model_name,
                "parameter_terpilih": "default_produksi_v1",
                "model_deploy": model_name,
                "parameter_deploy": "default_produksi_v1",
                "jumlah_data_training": int(len(frame.dropna(subset=FEATURE_COLUMNS + ["target"]).loc[lambda data: pd.to_datetime(data["target_date"]) <= pd.Timestamp(test["tanggal"].min())])),
                "jumlah_data_testing": test_metrics["n"],
                "mae": test_metrics["mae"],
                "rmse": test_metrics["rmse"],
                "mape": test_metrics["mape"],
                "smape": test_metrics["smape"],
                "naive_test_mae": naive_metrics["mae"],
                "model_validation_lolos_test_vs_naive": beats_naive,
                "interval_error_bawah_validation": lower - future,
                "interval_error_atas_validation": upper - future,
                "validation_mae": selected["validation_mae"],
                "validation_rmse": selected["validation_rmse"],
                "validation_mape": selected["validation_mape"],
                "validation_smape": selected["validation_smape"],
                "status_layak": status,
            })
            selected_parameters.append({
                "komoditas": commodity,
                "horizon_hari": horizon,
                "nama_model": model_name,
                "parameter": "default_produksi_v1",
            })
        print(f"  {index:02d}/{len(commodities)} {commodity}", flush=True)

    profile_df = pd.DataFrame(profiles, columns=["komoditas", "harga_valid", "missing_rate", "unique_price", "cv", "tipe_seri"])
    prediction_df = pd.DataFrame(predictions)
    evaluation_df = pd.DataFrame(evaluations)
    comparison_df = pd.DataFrame(all_comparisons)
    parameters_df = pd.DataFrame(selected_parameters)
    if prediction_df.empty:
        raise RuntimeError("Tidak ada prediksi yang berhasil dihasilkan.")

    print("[4/5] Menulis output audit", flush=True)
    prediction_file = output_dir / "prediksi_harga_komoditas_mojokerto_V3_FINAL_dengan_status.csv"
    profile_file = output_dir / "quality_profile_komoditas_V3_FINAL.csv"
    evaluation_file = output_dir / "evaluasi_final_per_horizon_V3_FINAL.csv"
    profile_df.to_csv(profile_file, index=False, encoding="utf-8-sig")
    prediction_df.to_csv(prediction_file, index=False, encoding="utf-8-sig")
    prediction_df.drop(columns=["status_layak"], errors="ignore").to_csv(
        output_dir / "prediksi_harga_komoditas_mojokerto_V3_FINAL.csv", index=False, encoding="utf-8-sig"
    )
    evaluation_df.to_csv(evaluation_file, index=False, encoding="utf-8-sig")
    comparison_df.to_csv(output_dir / "perbandingan_9_model_per_komoditas_horizon_V3_FINAL.csv", index=False, encoding="utf-8-sig")
    comparison_df.to_csv(output_dir / "ringkasan_evaluasi_9_model_V3_FINAL.csv", index=False, encoding="utf-8-sig")
    parameters_df.to_csv(output_dir / "parameter_model_terbaik_V3_FINAL.csv", index=False, encoding="utf-8-sig")
    pd.DataFrame({"urutan": range(1, 10), "nama_model": MODEL_CATALOG}).to_csv(output_dir / "katalog_9_model_V3_FINAL.csv", index=False, encoding="utf-8-sig")
    audit = comparison_df.groupby(["komoditas", "horizon_hari"], as_index=False).agg(
        jumlah_model=("nama_model", "count"), jumlah_model_valid=("validation_mae", "count")
    ) if not comparison_df.empty else pd.DataFrame(columns=["komoditas", "horizon_hari", "jumlah_model", "jumlah_model_valid"])
    audit.to_csv(output_dir / "audit_jumlah_9_model_V3_FINAL.csv", index=False, encoding="utf-8-sig")
    with open(output_dir / "prediksi_harga_komoditas_mojokerto_V3_FINAL.json", "w", encoding="utf-8") as handle:
        json.dump(safe_json_records(prediction_df), handle, ensure_ascii=False, indent=2, default=str)
    with open(output_dir / "ringkasan_evaluasi_9_model_V3_FINAL.json", "w", encoding="utf-8") as handle:
        json.dump(safe_json_records(comparison_df), handle, ensure_ascii=False, indent=2, default=str)

    status_counts = prediction_df["status_layak"].value_counts().to_dict()
    summary = {
        "version": args.version,
        "data_last_date": cutoff.strftime("%Y-%m-%d"),
        "generated_at": generated_at,
        "commodity_count": int(len(commodities)),
        "modeled_commodity_count": int(prediction_df["komoditas"].nunique()),
        "profile_count": int(len(profile_df)),
        "prediction_count": int(len(prediction_df)),
        "status_summary": {str(k): int(v) for k, v in status_counts.items()},
        "model_catalog": MODEL_CATALOG,
        "runtime_seconds": int(round(time.monotonic() - started)),
        "validation": "two expanding temporal validation blocks plus final holdout",
        "publication_gate": f"MAPE <= {args.max_mape}% and MAE <= Naive MAE",
    }
    with open(output_dir / "run_summary.json", "w", encoding="utf-8") as handle:
        json.dump(summary, handle, ensure_ascii=False, indent=2)
    print("[5/5] Selesai: " + json.dumps(summary, ensure_ascii=False), flush=True)
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(f"PREDICTION_PIPELINE_ERROR: {type(exc).__name__}: {exc}", file=sys.stderr, flush=True)
        raise
