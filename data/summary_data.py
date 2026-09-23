import zipfile
import xml.etree.ElementTree as ET
import os

data_dir = r"c:\Users\Aerio\PKL\trading penjualan\laravel\data"

def get_shared_strings(z):
    shared = []
    if 'xl/sharedStrings.xml' in z.namelist():
        tree = ET.fromstring(z.read('xl/sharedStrings.xml'))
        for si in tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}si'):
            text = "".join(t.text for t in si.findall('.//{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t') if t.text)
            shared.append(text)
    return shared

# 1. KARTU HUTANG
with zipfile.ZipFile(os.path.join(data_dir, "Kartu Hutang per 31 Aug 26.xlsx")) as z:
    shared = get_shared_strings(z)
    ws_tree = ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
    rows = ws_tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheetData/{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row')
    
    hutang_records = []
    for r in rows[4:]: # row 5 onwards
        row_dict = {}
        for c in r.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c'):
            col = "".join(filter(str.isalpha, c.attrib.get('r')))
            val = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
            cell_val = val.text if val is not None else None
            if c.attrib.get('t') == 's' and cell_val is not None:
                cell_val = shared[int(cell_val)]
            row_dict[col] = cell_val
        if row_dict.get('B'): # Supplier
            try:
                total = float(row_dict.get('E', 0) or 0)
                paid = float(row_dict.get('F', 0) or 0)
                sisa = float(row_dict.get('G', 0) or 0)
            except:
                continue
            hutang_records.append({
                'date': row_dict.get('A'),
                'supplier': row_dict.get('B'),
                'pi_code': row_dict.get('C'),
                'due_date': row_dict.get('D'),
                'total': total,
                'paid': paid,
                'sisa': sisa
            })

suppliers = set(r['supplier'] for r in hutang_records)
total_hutang_sisa = sum(r['sisa'] for r in hutang_records)
print(f"KARTU HUTANG: {len(hutang_records)} faktur dari {len(suppliers)} supplier. Total Sisa Hutang: Rp {total_hutang_sisa:,.0f}")
print("Top 5 Supplier Hutang Terbesar:")
supp_totals = {}
for r in hutang_records:
    supp_totals[r['supplier']] = supp_totals.get(r['supplier'], 0) + r['sisa']
for s, amt in sorted(supp_totals.items(), key=lambda x: x[1], reverse=True)[:5]:
    print(f"  - {s}: Rp {amt:,.0f}")

# 2. KARTU PIUTANG
with zipfile.ZipFile(os.path.join(data_dir, "Kartu Piutang per 31 Aug 26.xlsx")) as z:
    shared = get_shared_strings(z)
    ws_tree = ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
    rows = ws_tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheetData/{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row')
    
    piutang_records = []
    for r in rows[4:]:
        row_dict = {}
        for c in r.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c'):
            col = "".join(filter(str.isalpha, c.attrib.get('r')))
            val = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
            cell_val = val.text if val is not None else None
            if c.attrib.get('t') == 's' and cell_val is not None:
                cell_val = shared[int(cell_val)]
            row_dict[col] = cell_val
        if row_dict.get('B'): # Customer
            try:
                total = float(row_dict.get('F', 0) or 0)
                paid = float(row_dict.get('G', 0) or 0)
                sisa = float(row_dict.get('H', 0) or 0)
            except:
                continue
            piutang_records.append({
                'date': row_dict.get('A'),
                'customer': row_dict.get('B'),
                'po_number': row_dict.get('C'),
                'invoice_number': row_dict.get('D'),
                'due_date': row_dict.get('E'),
                'total': total,
                'paid': paid,
                'sisa': sisa
            })

customers = set(r['customer'] for r in piutang_records)
total_piutang_sisa = sum(r['sisa'] for r in piutang_records)
print(f"\nKARTU PIUTANG: {len(piutang_records)} invoice dari {len(customers)} customer. Total Sisa Piutang: Rp {total_piutang_sisa:,.0f}")
print("Top 5 Customer Piutang Terbesar:")
cust_totals = {}
for r in piutang_records:
    cust_totals[r['customer']] = cust_totals.get(r['customer'], 0) + r['sisa']
for c, amt in sorted(cust_totals.items(), key=lambda x: x[1], reverse=True)[:5]:
    print(f"  - {c}: Rp {amt:,.0f}")

# 3. STOK BARANG
with zipfile.ZipFile(os.path.join(data_dir, "Stok Barang 31 Aug 2026.xlsx")) as z:
    shared = get_shared_strings(z)
    ws_tree = ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
    rows = ws_tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheetData/{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row')
    
    stok_items = {}
    current_product = None
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
        if b_val and b_val not in ['Barang', 'Laporan Stok Barang (Detail)', 'Periode : 01-08-2026 s.d. 31-08-2026', 'Gudang : GUDANG SGF']:
            current_product = b_val
            
        if row_dict.get('G') == 'Total : ' and current_product:
            ending_qty = float(row_dict.get('J', 0) or 0)
            stok_items[current_product] = ending_qty

total_qty = sum(stok_items.values())
items_with_stock = {k: v for k, v in stok_items.items() if v > 0}
print(f"\nSTOK BARANG: {len(stok_items)} total produk terdata ({len(items_with_stock)} memiliki stok > 0). Total Fisik: {total_qty:,.0f} unit")
print("Contoh 5 Barang dengan Stok Akhir Terbesar:")
for p, q in sorted(items_with_stock.items(), key=lambda x: x[1], reverse=True)[:5]:
    print(f"  - {p}: {q:,.0f} unit")
