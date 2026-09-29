import argparse
import csv
import re
from pathlib import Path
from xml.etree import ElementTree as ET
from zipfile import ZipFile


MAIN_NS = "http://schemas.openxmlformats.org/spreadsheetml/2006/main"
REL_NS = "http://schemas.openxmlformats.org/officeDocument/2006/relationships"
NS = {"m": MAIN_NS}


def workbook_sheet_names(path: Path):
    with ZipFile(path) as archive:
        workbook = ET.fromstring(archive.read("xl/workbook.xml"))
        return [item.attrib["name"] for item in workbook.find("m:sheets", NS)]


def column_index(reference: str) -> int:
    letters = re.match(r"[A-Z]+", reference).group(0)
    value = 0
    for letter in letters:
        value = value * 26 + ord(letter) - 64
    return value - 1


def read_cell(cell, shared_strings):
    inline = cell.find("m:is/m:t", NS)
    value = cell.find("m:v", NS)
    if inline is not None:
        return inline.text or ""
    if value is None:
        return ""
    if cell.attrib.get("t") == "s":
        return shared_strings[int(value.text)]
    return value.text or ""


def sheet_rows(path: Path, sheet_name: str | None = None):
    with ZipFile(path) as archive:
        shared_strings = []
        if "xl/sharedStrings.xml" in archive.namelist():
            root = ET.fromstring(archive.read("xl/sharedStrings.xml"))
            shared_strings = [
                "".join(node.text or "" for node in item.iterfind(".//m:t", NS))
                for item in root.findall("m:si", NS)
            ]

        workbook = ET.fromstring(archive.read("xl/workbook.xml"))
        relationships = ET.fromstring(archive.read("xl/_rels/workbook.xml.rels"))
        targets = {relation.attrib["Id"]: relation.attrib["Target"] for relation in relationships}
        sheets = list(workbook.find("m:sheets", NS))
        if not sheets:
            raise ValueError("Workbook tidak memiliki sheet")
        # Utamakan sheet SEMUA DATA. Jika file pengguna hanya berisi satu pasar
        # dengan nama sheet berbeda, gunakan sheet pertama secara otomatis.
        sheet = next(
            (item for item in sheets if item.attrib["name"].strip().casefold() == (sheet_name or "").strip().casefold()),
            sheets[0],
        )
        relation_id = sheet.attrib[f"{{{REL_NS}}}id"]
        target = targets[relation_id].lstrip("/")
        sheet_path = target if target.startswith("xl/") else f"xl/{target}"

        root = ET.fromstring(archive.read(sheet_path))
        for row in root.findall(".//m:sheetData/m:row", NS):
            values = [""] * 12
            for cell in row.findall("m:c", NS):
                index = column_index(cell.attrib["r"])
                if index < len(values):
                    values[index] = read_cell(cell, shared_strings)
            yield values


def import_rows(path: Path):
    """Gabungkan seluruh sheet data harian, atau gunakan SEMUA DATA jika tersedia."""
    names = workbook_sheet_names(path)
    combined = next((name for name in names if name.strip().casefold() == "semua data"), None)
    candidates = [combined] if combined else names
    header_written = False

    for name in candidates:
        rows = iter(sheet_rows(path, name))
        header = next(rows, None)
        if header is None:
            continue
        normalized = {str(value).strip().casefold() for value in header}
        is_data_sheet = (
            {"tanggal", "pasar"}.issubset(normalized)
            and bool({"komoditas", "nama komoditas", "nama bahan pokok"} & normalized)
            and bool({"harga", "harga sekarang", "price"} & normalized)
        )
        if not is_data_sheet:
            continue
        if not header_written:
            yield header
            header_written = True
        yield from rows

    if not header_written:
        # Pertahankan pesan validasi kolom yang informatif untuk file satu-sheet
        # yang tidak mengikuti format importer.
        yield from sheet_rows(path, "SEMUA DATA")


def main():
    parser = argparse.ArgumentParser(description="Ekstrak sheet SEMUA DATA dari XLSX Siskaperbapo.")
    parser.add_argument("inputs", nargs="+", type=Path)
    parser.add_argument("--output-dir", required=True, type=Path)
    args = parser.parse_args()
    args.output_dir.mkdir(parents=True, exist_ok=True)

    for source in args.inputs:
        destination = args.output_dir / f"{source.stem}.csv"
        row_count = 0
        with destination.open("w", encoding="utf-8-sig", newline="") as handle:
            writer = csv.writer(handle)
            for row in import_rows(source):
                writer.writerow(row)
                row_count += 1
        print(f"{source.name} -> {destination.name}: {row_count - 1} data rows")


if __name__ == "__main__":
    main()
