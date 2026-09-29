from collections import Counter
from pathlib import Path
from xml.etree import ElementTree as ET
from zipfile import ZipFile


FILES = list(Path(r"C:\Users\endo1\Downloads").glob("HASIL FINAL PASAR *.xlsx"))
NS = {"m": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}


def text(cell, shared):
    inline = cell.find("m:is/m:t", NS)
    value = cell.find("m:v", NS)
    if inline is not None:
        return inline.text or ""
    if value is None:
        return ""
    return shared[int(value.text)] if cell.attrib.get("t") == "s" else value.text


def rows_from_all_data(path):
    with ZipFile(path) as archive:
        shared = []
        if "xl/sharedStrings.xml" in archive.namelist():
            root = ET.fromstring(archive.read("xl/sharedStrings.xml"))
            shared = ["".join(node.text or "" for node in item.iterfind(".//m:t", NS)) for item in root.findall("m:si", NS)]
        workbook = ET.fromstring(archive.read("xl/workbook.xml"))
        relationships = ET.fromstring(archive.read("xl/_rels/workbook.xml.rels"))
        targets = {rel.attrib["Id"]: rel.attrib["Target"] for rel in relationships}
        sheet = next(item for item in workbook.find("m:sheets", NS) if item.attrib["name"] == "SEMUA DATA")
        relation_id = sheet.attrib["{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id"]
        target = targets[relation_id].lstrip("/")
        sheet_path = target if target.startswith("xl/") else f"xl/{target}"
        root = ET.fromstring(archive.read(sheet_path))
        for row in root.findall(".//m:sheetData/m:row", NS):
            result = [text(cell, shared) for cell in row.findall("m:c", NS)]
            result += [""] * (12 - len(result))
            yield result[:12]


for path in sorted(FILES):
    iterator = rows_from_all_data(path)
    header = next(iterator)
    rows = list(iterator)
    dates = [row[0] for row in rows if row[0]]
    prices = [float(row[9] or 0) for row in rows]
    keys = [(row[0], row[2], row[6]) for row in rows]
    duplicates = sum(count - 1 for count in Counter(keys).values() if count > 1)
    print(f"FILE={path.name}")
    print(f" rows={len(rows)} dates={min(dates)}..{max(dates)} unique_dates={len(set(dates))}")
    print(f" market_ids={sorted(set(row[2] for row in rows))} markets={sorted(set(row[3] for row in rows))}")
    print(f" categories={len(set(row[5] for row in rows))} commodities={len(set(row[6] for row in rows))} units={len(set(row[7] for row in rows))}")
    print(f" zero_prices={sum(price == 0 for price in prices)} positive_prices={sum(price > 0 for price in prices)} duplicate_keys={duplicates}")
    print(f" headers={header}")
