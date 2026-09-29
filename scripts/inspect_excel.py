from pathlib import Path
from xml.etree import ElementTree as ET
from zipfile import ZipFile


FILES = [
    Path(r"C:\Users\endo1\Downloads\HASIL FINAL PASAR GEMPOL KEREP.xlsx"),
    Path(r"C:\Users\endo1\Downloads\HASIL FINAL PASAR KEDUNG MALING.xlsx"),
    Path(r"C:\Users\endo1\Downloads\HASIL FINAL PASAR MOJOSARI.xlsx"),
    Path(r"C:\Users\endo1\Downloads\HASIL FINAL PASAR POH JEJER.xlsx"),
]
NS = {"m": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}


def cell_value(cell, shared_strings):
    cell_type = cell.attrib.get("t")
    value = cell.find("m:v", NS)
    inline = cell.find("m:is/m:t", NS)
    if inline is not None:
        return inline.text
    if value is None:
        return None
    if cell_type == "s":
        return shared_strings[int(value.text)]
    return value.text


for path in FILES:
    print(f"\nFILE: {path.name} | EXISTS: {path.exists()}")
    if not path.exists():
        continue
    print(f"SIZE: {path.stat().st_size} bytes")

    with ZipFile(path) as archive:
        shared_strings = []
        if "xl/sharedStrings.xml" in archive.namelist():
            root = ET.fromstring(archive.read("xl/sharedStrings.xml"))
            for item in root.findall("m:si", NS):
                shared_strings.append("".join(text.text or "" for text in item.iterfind(".//m:t", NS)))

        workbook = ET.fromstring(archive.read("xl/workbook.xml"))
        relationships = ET.fromstring(archive.read("xl/_rels/workbook.xml.rels"))
        targets = {rel.attrib["Id"]: rel.attrib["Target"] for rel in relationships}
        sheets = []
        for sheet in workbook.find("m:sheets", NS):
            relation_id = sheet.attrib["{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id"]
            target = targets[relation_id].lstrip("/")
            sheet_path = target if target.startswith("xl/") else f"xl/{target}"
            sheets.append((sheet.attrib["name"], sheet_path))
        print("SHEETS:", [name for name, _ in sheets])

        for sheet_name, sheet_path in sheets:
            sheet_root = ET.fromstring(archive.read(sheet_path))
            rows = sheet_root.findall(".//m:sheetData/m:row", NS)
            print(f" SHEET: {sheet_name} | XML ROWS: {len(rows)}")
            displayed = 0
            for row in rows:
                values = [cell_value(cell, shared_strings) for cell in row.findall("m:c", NS)]
                if not any(value not in (None, "") for value in values):
                    continue
                print(f"  ROW {row.attrib.get('r')}: {values[:15]}")
                displayed += 1
                if displayed >= 12:
                    break
