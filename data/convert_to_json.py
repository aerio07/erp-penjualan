import zipfile
import xml.etree.ElementTree as ET
import os
import json
from datetime import datetime

data_dir = r"c:\Users\Aerio\PKL\trading penjualan\laravel\data"

def get_shared_strings(z):
    shared = []
    if 'xl/sharedStrings.xml' in z.namelist():
        tree = ET.fromstring(z.read('xl/sharedStrings.xml'))
        for si in tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}si'):
            text = "".join(t.text for t in si.findall('.//{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t') if t.text)
            shared.append(text)
    return shared

def parse_date(date_str):
    if not date_str or date_str == '-':
        return None
    date_str = date_str.strip()
    try:
        return datetime.strptime(date_str, "%d-%m-%Y").strftime("%Y-%m-%d")
    except:
        pass
    try:
        return datetime.strptime(date_str, "%Y-%m-%d").strftime("%Y-%m-%d")
    except:
        pass
    return None

# 1. PARSE KARTU HUTANG
print("Parsing Kartu Hutang...")
with zipfile.ZipFile(os.path.join(data_dir, "Kartu Hutang per 31 Aug 26.xlsx")) as z:
    shared = get_shared_strings(z)
    ws_tree = ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
    rows = ws_tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheetData/{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row')
    
    hutang_list = []
    for r in rows:
        r_num = int(r.attrib.get('r', 0))
        if r_num < 5:
            continue
            
        row_dict = {}
        for c in r.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c'):
            col = "".join(filter(str.isalpha, c.attrib.get('r')))
            val = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
            cell_val = val.text if val is not None else None
            if c.attrib.get('t') == 's' and cell_val is not None:
                cell_val = shared[int(cell_val)]
            row_dict[col] = cell_val
        
        supplier_name = row_dict.get('B')
        pi_code = row_dict.get('C')
        
        # Stop at TOTAL row
        if 'TOTAL' in str(row_dict.get('D', '')).upper():
            break
            
        if supplier_name and pi_code:
            tgl = parse_date(row_dict.get('A')) or '2026-08-31'
            jt = parse_date(row_dict.get('D')) or tgl
            total = float(row_dict.get('E', 0) or 0)
            paid = float(row_dict.get('F', 0) or 0)
            sisa = float(row_dict.get('G', 0) or 0)
            status = row_dict.get('H', 'Open')
            
            hutang_list.append({
                'supplier_name': supplier_name.strip(),
                'pi_code': pi_code.strip(),
                'invoice_date': tgl,
                'due_date': jt,
                'total_amount': total,
                'paid_amount': paid,
                'outstanding_amount': sisa,
                'status': status
            })

tot_hutang = sum(h['outstanding_amount'] for h in hutang_list)
print(f"Hutang rows: {len(hutang_list)}, Total Sisa: {tot_hutang:,.0f} (Target Agnes: 37,487,726,193)")
with open(os.path.join(data_dir, "hutang_cutoff.json"), "w", encoding="utf-8") as f:
    json.dump(hutang_list, f, indent=2)

# 2. PARSE KARTU PIUTANG
print("Parsing Kartu Piutang...")
with zipfile.ZipFile(os.path.join(data_dir, "Kartu Piutang per 31 Aug 26.xlsx")) as z:
    shared = get_shared_strings(z)
    ws_tree = ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
    rows = ws_tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheetData/{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row')
    
    piutang_list = []
    for r in rows:
        r_num = int(r.attrib.get('r', 0))
        if r_num < 5:
            continue
            
        row_dict = {}
        for c in r.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c'):
            col = "".join(filter(str.isalpha, c.attrib.get('r')))
            val = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
            cell_val = val.text if val is not None else None
            if c.attrib.get('t') == 's' and cell_val is not None:
                cell_val = shared[int(cell_val)]
            row_dict[col] = cell_val
        
        customer_name = row_dict.get('B')
        faktur = row_dict.get('D')
        
        # Stop at TOTAL row
        if 'TOTAL' in str(row_dict.get('D', '')).upper():
            break
            
        if customer_name and faktur:
            tgl = parse_date(row_dict.get('A')) or '2026-08-31'
            jt = parse_date(row_dict.get('E')) or tgl
            po_num = (row_dict.get('C') or '').strip()
            total = float(row_dict.get('F', 0) or 0)
            paid = float(row_dict.get('G', 0) or 0)
            sisa = float(row_dict.get('H', 0) or 0)
            
            piutang_list.append({
                'customer_name': customer_name.strip(),
                'po_number': po_num if po_num else None,
                'invoice_number': faktur.strip(),
                'invoice_date': tgl,
                'due_date': jt,
                'total_amount': total,
                'paid_amount': paid,
                'outstanding_amount': sisa,
            })

tot_piutang = sum(p['outstanding_amount'] for p in piutang_list)
print(f"Piutang rows: {len(piutang_list)}, Total Sisa: {tot_piutang:,.0f} (Target Agnes: 39,116,724,651)")
with open(os.path.join(data_dir, "piutang_cutoff.json"), "w", encoding="utf-8") as f:
    json.dump(piutang_list, f, indent=2)

# 3. PARSE STOK BARANG
print("Parsing Stok Barang...")
with zipfile.ZipFile(os.path.join(data_dir, "Stok Barang 31 Aug 2026.xlsx")) as z:
    shared = get_shared_strings(z)
    ws_tree = ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
    rows = ws_tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheetData/{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row')
    
    stok_list = []
    current_product = None
    seen_products = set()
    
    for r in rows:
        row_dict = {}
        for c in r.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c'):
            col = "".join(filter(str.isalpha, c.attrib.get('r')))
            val = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
            cell_val = val.text if val is not None else None
            if c.attrib.get('t') == 's' and cell_val is not None:
                cell_val = shared[int(cell_val)]
            row_dict[col] = cell_val
        
        b_val = row_dict.get('B')
        if b_val and b_val.strip() not in ['Barang', 'Laporan Stok Barang (Detail)', 'Periode : 01-08-2026 s.d. 31-08-2026', 'Gudang : GUDANG SGF']:
            current_product = b_val.strip()
            
        if row_dict.get('G') == 'Total : ' and current_product and current_product not in seen_products:
            ending_qty = int(float(row_dict.get('J', 0) or 0))
            seen_products.add(current_product)
            stok_list.append({
                'product_name': current_product,
                'warehouse_name': 'GUDANG SGF',
                'ending_qty': ending_qty,
                'as_of_date': '2026-08-31'
            })

print(f"Stok rows: {len(stok_list)}")
with open(os.path.join(data_dir, "stok_cutoff.json"), "w", encoding="utf-8") as f:
    json.dump(stok_list, f, indent=2)

print("All parsing completed successfully!")
