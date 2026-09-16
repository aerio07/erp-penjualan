<?php

namespace App\Services;

class RegionService
{
    /**
     * Get list of provinces with their cities/regencies and details.
     * Structured data covering Indonesia's provinces and major regencies.
     */
    public static function getProvinces(): array
    {
        return [
            ['id' => 'DKI_JAKARTA', 'name' => 'DKI Jakarta'],
            ['id' => 'JAWA_BARAT', 'name' => 'Jawa Barat'],
            ['id' => 'JAWA_TENGAH', 'name' => 'Jawa Tengah'],
            ['id' => 'DI_YOGYAKARTA', 'name' => 'DI Yogyakarta'],
            ['id' => 'JAWA_TIMUR', 'name' => 'Jawa Timur'],
            ['id' => 'BANTEN', 'name' => 'Banten'],
            ['id' => 'BALI', 'name' => 'Bali'],
            ['id' => 'SUMATERA_UTARA', 'name' => 'Sumatera Utara'],
            ['id' => 'SUMATERA_BARAT', 'name' => 'Sumatera Barat'],
            ['id' => 'SUMATERA_SELATAN', 'name' => 'Sumatera Selatan'],
            ['id' => 'RIAU', 'name' => 'Riau'],
            ['id' => 'KEPULAUAN_RIAU', 'name' => 'Kepulauan Riau'],
            ['id' => 'LAMPUNG', 'name' => 'Lampung'],
            ['id' => 'KALIMANTAN_BARAT', 'name' => 'Kalimantan Barat'],
            ['id' => 'KALIMANTAN_TIMUR', 'name' => 'Kalimantan Timur'],
            ['id' => 'KALIMANTAN_SELATAN', 'name' => 'Kalimantan Selatan'],
            ['id' => 'SULAWESI_SELATAN', 'name' => 'Sulawesi Selatan'],
            ['id' => 'SULAWESI_UTARA', 'name' => 'Sulawesi Utara'],
            ['id' => 'PAPUA', 'name' => 'Papua'],
            ['id' => 'NUSA_TENGGARA_BARAT', 'name' => 'Nusa Tenggara Barat'],
            ['id' => 'NUSA_TENGGARA_TIMUR', 'name' => 'Nusa Tenggara Timur'],
        ];
    }

    /**
     * Get regencies/cities by province ID.
     */
    public static function getRegencies(string $provinceId): array
    {
        $data = [
            'DKI_JAKARTA' => [
                ['id' => 'JAKARTA_PUSAT', 'name' => 'Kota Jakarta Pusat'],
                ['id' => 'JAKARTA_SELATAN', 'name' => 'Kota Jakarta Selatan'],
                ['id' => 'JAKARTA_BARAT', 'name' => 'Kota Jakarta Barat'],
                ['id' => 'JAKARTA_TIMUR', 'name' => 'Kota Jakarta Timur'],
                ['id' => 'JAKARTA_UTARA', 'name' => 'Kota Jakarta Utara'],
                ['id' => 'KEP_SERIBU', 'name' => 'Kab. Kepulauan Seribu'],
            ],
            'JAWA_BARAT' => [
                ['id' => 'KOTA_BANDUNG', 'name' => 'Kota Bandung'],
                ['id' => 'KAB_BANDUNG', 'name' => 'Kab. Bandung'],
                ['id' => 'KAB_BANDUNG_BARAT', 'name' => 'Kab. Bandung Barat'],
                ['id' => 'KOTA_BEKASI', 'name' => 'Kota Bekasi'],
                ['id' => 'KAB_BEKASI', 'name' => 'Kab. Bekasi'],
                ['id' => 'KOTA_BOGOR', 'name' => 'Kota Bogor'],
                ['id' => 'KAB_BOGOR', 'name' => 'Kab. Bogor'],
                ['id' => 'KOTA_DEPOK', 'name' => 'Kota Depok'],
                ['id' => 'KOTA_CIMAHI', 'name' => 'Kota Cimahi'],
                ['id' => 'KOTA_CIREBON', 'name' => 'Kota Cirebon'],
                ['id' => 'KAB_CIREBON', 'name' => 'Kab. Cirebon'],
                ['id' => 'KOTA_SUKABUMI', 'name' => 'Kota Sukabumi'],
                ['id' => 'KAB_SUKABUMI', 'name' => 'Kab. Sukabumi'],
                ['id' => 'KOTA_TASIKMALAYA', 'name' => 'Kota Tasikmalaya'],
                ['id' => 'KAB_TASIKMALAYA', 'name' => 'Kab. Tasikmalaya'],
                ['id' => 'KAB_KARAWANG', 'name' => 'Kab. Karawang'],
                ['id' => 'KAB_PURWAKARTA', 'name' => 'Kab. Purwakarta'],
                ['id' => 'KAB_SUBANG', 'name' => 'Kab. Subang'],
                ['id' => 'KAB_SUMEDANG', 'name' => 'Kab. Sumedang'],
                ['id' => 'KAB_GARUT', 'name' => 'Kab. Garut'],
                ['id' => 'KAB_INDRAMAYU', 'name' => 'Kab. Indramayu'],
                ['id' => 'KAB_MAJALENGKA', 'name' => 'Kab. Majalengka'],
                ['id' => 'KAB_KUNINGAN', 'name' => 'Kab. Kuningan'],
                ['id' => 'KAB_CIAMIS', 'name' => 'Kab. Ciamis'],
                ['id' => 'KAB_PANGANDARAN', 'name' => 'Kab. Pangandaran'],
                ['id' => 'KOTA_BANJAR', 'name' => 'Kota Banjar'],
            ],
            'BANTEN' => [
                ['id' => 'KOTA_TANGERANG', 'name' => 'Kota Tangerang'],
                ['id' => 'KOTA_TANGERANG_SELATAN', 'name' => 'Kota Tangerang Selatan'],
                ['id' => 'KAB_TANGERANG', 'name' => 'Kab. Tangerang'],
                ['id' => 'KOTA_SERANG', 'name' => 'Kota Serang'],
                ['id' => 'KAB_SERANG', 'name' => 'Kab. Serang'],
                ['id' => 'KOTA_CILEGON', 'name' => 'Kota Cilegon'],
                ['id' => 'KAB_LEBAK', 'name' => 'Kab. Lebak'],
                ['id' => 'KAB_PANDEGLANG', 'name' => 'Kab. Pandeglang'],
            ],
            'JAWA_TENGAH' => [
                ['id' => 'KOTA_SEMARANG', 'name' => 'Kota Semarang'],
                ['id' => 'KOTA_SURAKARTA', 'name' => 'Kota Surakarta (Solo)'],
                ['id' => 'KOTA_MAGELANG', 'name' => 'Kota Magelang'],
                ['id' => 'KOTA_PEKALONGAN', 'name' => 'Kota Pekalongan'],
                ['id' => 'KOTA_SALATIGA', 'name' => 'Kota Salatiga'],
                ['id' => 'KOTA_TEGAL', 'name' => 'Kota Tegal'],
                ['id' => 'KAB_BANYUMAS', 'name' => 'Kab. Banyumas (Purwokerto)'],
                ['id' => 'KAB_KUDUS', 'name' => 'Kab. Kudus'],
                ['id' => 'KAB_KLATEN', 'name' => 'Kab. Klaten'],
                ['id' => 'KAB_SEMARANG', 'name' => 'Kab. Semarang'],
                ['id' => 'KAB_BOYOLALI', 'name' => 'Kab. Boyolali'],
                ['id' => 'KAB_CILACAP', 'name' => 'Kab. Cilacap'],
                ['id' => 'KAB_TEGAL', 'name' => 'Kab. Tegal'],
                ['id' => 'KAB_BREBES', 'name' => 'Kab. Brebes'],
                ['id' => 'KAB_PATI', 'name' => 'Kab. Pati'],
                ['id' => 'KAB_JEPARA', 'name' => 'Kab. Jepara'],
            ],
            'DI_YOGYAKARTA' => [
                ['id' => 'KOTA_YOGYAKARTA', 'name' => 'Kota Yogyakarta'],
                ['id' => 'KAB_SLEMAN', 'name' => 'Kab. Sleman'],
                ['id' => 'KAB_BANTUL', 'name' => 'Kab. Bantul'],
                ['id' => 'KAB_GUNUNGKIDUL', 'name' => 'Kab. Gunungkidul'],
                ['id' => 'KAB_KULON_PROGO', 'name' => 'Kab. Kulon Progo'],
            ],
            'JAWA_TIMUR' => [
                ['id' => 'KOTA_SURABAYA', 'name' => 'Kota Surabaya'],
                ['id' => 'KOTA_MALANG', 'name' => 'Kota Malang'],
                ['id' => 'KAB_MALANG', 'name' => 'Kab. Malang'],
                ['id' => 'KAB_SIDOARJO', 'name' => 'Kab. Sidoarjo'],
                ['id' => 'KAB_GRESIK', 'name' => 'Kab. Gresik'],
                ['id' => 'KOTA_KEDIRI', 'name' => 'Kota Kediri'],
                ['id' => 'KAB_KEDIRI', 'name' => 'Kab. Kediri'],
                ['id' => 'KOTA_MADIUN', 'name' => 'Kota Madiun'],
                ['id' => 'KOTA_MOJOKERTO', 'name' => 'Kota Mojokerto'],
                ['id' => 'KAB_MOJOKERTO', 'name' => 'Kab. Mojokerto'],
                ['id' => 'KOTA_PASURUAN', 'name' => 'Kota Pasuruan'],
                ['id' => 'KAB_PASURUAN', 'name' => 'Kab. Pasuruan'],
                ['id' => 'KOTA_BATU', 'name' => 'Kota Batu'],
                ['id' => 'KAB_JEMBER', 'name' => 'Kab. Jember'],
                ['id' => 'KAB_BANYUWANGI', 'name' => 'Kab. Banyuwangi'],
            ],
            'BALI' => [
                ['id' => 'KOTA_DENPASAR', 'name' => 'Kota Denpasar'],
                ['id' => 'KAB_BADUNG', 'name' => 'Kab. Badung'],
                ['id' => 'KAB_GIANYAR', 'name' => 'Kab. Gianyar'],
                ['id' => 'KAB_TABANAN', 'name' => 'Kab. Tabanan'],
                ['id' => 'KAB_BULELENG', 'name' => 'Kab. Buleleng'],
            ],
            'SUMATERA_UTARA' => [
                ['id' => 'KOTA_MEDAN', 'name' => 'Kota Medan'],
                ['id' => 'KAB_DELI_SERDANG', 'name' => 'Kab. Deli Serdang'],
                ['id' => 'KOTA_BINJAI', 'name' => 'Kota Binjai'],
                ['id' => 'KOTA_PEMATANGSIANTAR', 'name' => 'Kota Pematangsiantar'],
            ],
            'SUMATERA_BARAT' => [
                ['id' => 'KOTA_PADANG', 'name' => 'Kota Padang'],
                ['id' => 'KOTA_BUKITTINGGI', 'name' => 'Kota Bukittinggi'],
            ],
            'SUMATERA_SELATAN' => [
                ['id' => 'KOTA_PALEMBANG', 'name' => 'Kota Palembang'],
                ['id' => 'KAB_BANYUASIN', 'name' => 'Kab. Banyuasin'],
            ],
            'RIAU' => [
                ['id' => 'KOTA_PEKANBARU', 'name' => 'Kota Pekanbaru'],
                ['id' => 'KOTA_DUMAI', 'name' => 'Kota Dumai'],
            ],
            'KEPULAUAN_RIAU' => [
                ['id' => 'KOTA_BATAM', 'name' => 'Kota Batam'],
                ['id' => 'KOTA_TANJUNGPINANG', 'name' => 'Kota Tanjungpinang'],
            ],
            'LAMPUNG' => [
                ['id' => 'KOTA_BANDAR_LAMPUNG', 'name' => 'Kota Bandar Lampung'],
                ['id' => 'KOTA_METRO', 'name' => 'Kota Metro'],
            ],
            'KALIMANTAN_TIMUR' => [
                ['id' => 'KOTA_SAMARINDA', 'name' => 'Kota Samarinda'],
                ['id' => 'KOTA_BALIKPAPAN', 'name' => 'Kota Balikpapan'],
            ],
            'KALIMANTAN_BARAT' => [
                ['id' => 'KOTA_PONTIANAK', 'name' => 'Kota Pontianak'],
            ],
            'KALIMANTAN_SELATAN' => [
                ['id' => 'KOTA_BANJARMASIN', 'name' => 'Kota Banjarmasin'],
                ['id' => 'KOTA_BANJARBARU', 'name' => 'Kota Banjarbaru'],
            ],
            'SULAWESI_SELATAN' => [
                ['id' => 'KOTA_MAKASSAR', 'name' => 'Kota Makassar'],
                ['id' => 'KAB_GOWA', 'name' => 'Kab. Gowa'],
            ],
            'SULAWESI_UTARA' => [
                ['id' => 'KOTA_MANADO', 'name' => 'Kota Manado'],
            ],
            'PAPUA' => [
                ['id' => 'KOTA_JAYAPURA', 'name' => 'Kota Jayapura'],
            ],
            'NUSA_TENGGARA_BARAT' => [
                ['id' => 'KOTA_MATARAM', 'name' => 'Kota Mataram'],
            ],
            'NUSA_TENGGARA_TIMUR' => [
                ['id' => 'KOTA_KUPANG', 'name' => 'Kota Kupang'],
            ],
        ];

        return $data[$provinceId] ?? [];
    }

    /**
     * Get districts (kecamatan) by regency ID.
     */
    public static function getDistricts(string $regencyId): array
    {
        $data = [
            // Jakarta Pusat
            'JAKARTA_PUSAT' => [
                ['id' => 'GAMBIR', 'name' => 'Gambir'],
                ['id' => 'TANAH_ABANG', 'name' => 'Tanah Abang'],
                ['id' => 'MENTENG', 'name' => 'Menteng'],
                ['id' => 'SENEN', 'name' => 'Senen'],
                ['id' => 'CEMPAKA_PUTIH', 'name' => 'Cempaka Putih'],
                ['id' => 'JOHAR_BARU', 'name' => 'Johar Baru'],
                ['id' => 'KEMAYORAN', 'name' => 'Kemayoran'],
                ['id' => 'SAWAH_BESAR', 'name' => 'Sawah Besar'],
            ],
            // Jakarta Selatan
            'JAKARTA_SELATAN' => [
                ['id' => 'KEBAYORAN_BARU', 'name' => 'Kebayoran Baru'],
                ['id' => 'KEBAYORAN_LAMA', 'name' => 'Kebayoran Lama'],
                ['id' => 'CILANDAK', 'name' => 'Cilandak'],
                ['id' => 'MAMPANG_PRAPATAN', 'name' => 'Mampang Prapatan'],
                ['id' => 'PASAR_MINGGU', 'name' => 'Pasar Minggu'],
                ['id' => 'TEBET', 'name' => 'Tebet'],
                ['id' => 'SETIABUDI', 'name' => 'Setiabudi'],
                ['id' => 'PANCORAN', 'name' => 'Pancoran'],
                ['id' => 'JAGAKARSA', 'name' => 'Jagakarsa'],
                ['id' => 'PESANGGRAHAN', 'name' => 'Pesanggrahan'],
            ],
            // Jakarta Barat
            'JAKARTA_BARAT' => [
                ['id' => 'GROGOL_PETAMBURAN', 'name' => 'Grogol Petamburan'],
                ['id' => 'KEBON_JERUK', 'name' => 'Kebon Jeruk'],
                ['id' => 'PALMERAH', 'name' => 'Palmerah'],
                ['id' => 'KEMBANGAN', 'name' => 'Kembangan'],
                ['id' => 'TAMAN_SARI', 'name' => 'Taman Sari'],
                ['id' => 'TAMBORA', 'name' => 'Tambora'],
                ['id' => 'CENGKARENG', 'name' => 'Cengkareng'],
                ['id' => 'KALIDERES', 'name' => 'Kalideres'],
            ],
            // Jakarta Timur
            'JAKARTA_TIMUR' => [
                ['id' => 'MATRAMAN', 'name' => 'Matraman'],
                ['id' => 'PULOGADUNG', 'name' => 'Pulo Gadung'],
                ['id' => 'JATINEGARA', 'name' => 'Jatinegara'],
                ['id' => 'DUREN_SAWIT', 'name' => 'Duren Sawit'],
                ['id' => 'KRAMAT_JATI', 'name' => 'Kramat Jati'],
                ['id' => 'MAKASAR', 'name' => 'Makasar'],
                ['id' => 'PASAR_REBO', 'name' => 'Pasar Rebo'],
                ['id' => 'CIRACAS', 'name' => 'Ciracas'],
                ['id' => 'CIPAYUNG', 'name' => 'Cipayung'],
                ['id' => 'CAKUNG', 'name' => 'Cakung'],
            ],
            // Jakarta Utara
            'JAKARTA_UTARA' => [
                ['id' => 'PENJARINGAN', 'name' => 'Penjaringan'],
                ['id' => 'TANJUNG_PRIOK', 'name' => 'Tanjung Priok'],
                ['id' => 'KOJA', 'name' => 'Koja'],
                ['id' => 'CILINCING', 'name' => 'Cilincing'],
                ['id' => 'PADEMANGAN', 'name' => 'Pademangan'],
                ['id' => 'KELAPA_GADING', 'name' => 'Kelapa Gading'],
            ],
            // Kota Bandung
            'KOTA_BANDUNG' => [
                ['id' => 'COBLONG', 'name' => 'Coblong'],
                ['id' => 'SUKAJADI', 'name' => 'Sukajadi'],
                ['id' => 'ANDIR', 'name' => 'Andir'],
                ['id' => 'CICENDO', 'name' => 'Cicendo'],
                ['id' => 'SUMUR_BANDUNG', 'name' => 'Sumur Bandung'],
                ['id' => 'LENGKONG', 'name' => 'Lengkong'],
                ['id' => 'ASTANAANYAR', 'name' => 'Astana Anyar'],
                ['id' => 'BOJONGLOA_KALER', 'name' => 'Bojongloa Kaler'],
                ['id' => 'BUAHRATU', 'name' => 'Buahbatu'],
                ['id' => 'REGOL', 'name' => 'Regol'],
                ['id' => 'BATUNUNGGAL', 'name' => 'Batununggal'],
                ['id' => 'CIBEUNYING_KALER', 'name' => 'Cibeunying Kaler'],
                ['id' => 'KIARACONDONG', 'name' => 'Kiaracondong'],
                ['id' => 'ANTAPANI', 'name' => 'Antapani'],
                ['id' => 'ARCAMANIK', 'name' => 'Arcamanik'],
                ['id' => 'CIBIRU', 'name' => 'Cibiru'],
                ['id' => 'GEDEBAGE', 'name' => 'Gedebage'],
                ['id' => 'BANDUNG_KIDUL', 'name' => 'Bandung Kidul'],
            ],
            // Kota Bekasi
            'KOTA_BEKASI' => [
                ['id' => 'BEKASI_BARAT', 'name' => 'Bekasi Barat'],
                ['id' => 'BEKASI_TIMUR', 'name' => 'Bekasi Timur'],
                ['id' => 'BEKASI_UTARA', 'name' => 'Bekasi Utara'],
                ['id' => 'BEKASI_SELATAN', 'name' => 'Bekasi Selatan'],
                ['id' => 'RAWALUMBU', 'name' => 'Rawalumbu'],
                ['id' => 'MEDANSATRIA', 'name' => 'Medan Satria'],
                ['id' => 'BANTAR_GEBANG', 'name' => 'Bantar Gebang'],
                ['id' => 'PONDOK_GEDE', 'name' => 'Pondok Gede'],
                ['id' => 'JATIASIH', 'name' => 'Jatiasih'],
                ['id' => 'JATISAMPURNA', 'name' => 'Jatisampurna'],
                ['id' => 'MUSTIKA_JAYA', 'name' => 'Mustika Jaya'],
                ['id' => 'PONDOK_MELATI', 'name' => 'Pondok Melati'],
            ],
            // Kota Tangerang
            'KOTA_TANGERANG' => [
                ['id' => 'TANGERANG', 'name' => 'Tangerang'],
                ['id' => 'CIPONDOH', 'name' => 'Cipondoh'],
                ['id' => 'CILEDUG', 'name' => 'Ciledug'],
                ['id' => 'KARAWACI', 'name' => 'Karawaci'],
                ['id' => 'BATUCEPER', 'name' => 'Batuceper'],
                ['id' => 'BENDA', 'name' => 'Benda'],
                ['id' => 'JATIUWUNG', 'name' => 'Jatiuwung'],
                ['id' => 'PINANG', 'name' => 'Pinang'],
                ['id' => 'KARANG_TENGAH', 'name' => 'Karang Tengah'],
                ['id' => 'LARANGAN', 'name' => 'Larangan'],
            ],
            // Kota Tangerang Selatan
            'KOTA_TANGERANG_SELATAN' => [
                ['id' => 'SERPONG', 'name' => 'Serpong'],
                ['id' => 'SERPONG_UTARA', 'name' => 'Serpong Utara'],
                ['id' => 'PONDOK_AREN', 'name' => 'Pondok Aren'],
                ['id' => 'CIPUTAT', 'name' => 'Ciputat'],
                ['id' => 'CIPUTAT_TIMUR', 'name' => 'Ciputat Timur'],
                ['id' => 'PAMULANG', 'name' => 'Pamulang'],
                ['id' => 'SETU', 'name' => 'Setu'],
            ],
            // Kota Surabaya
            'KOTA_SURABAYA' => [
                ['id' => 'TEGALSARI', 'name' => 'Tegalsari'],
                ['id' => 'GENTENG', 'name' => 'Genteng'],
                ['id' => 'BUBUTAN', 'name' => 'Bubutan'],
                ['id' => 'SIMOKERTO', 'name' => 'Simokerto'],
                ['id' => 'GUBENG', 'name' => 'Gubeng'],
                ['id' => 'WONOKROMO', 'name' => 'Wonokromo'],
                ['id' => 'RUNGKUT', 'name' => 'Rungkut'],
                ['id' => 'SUKOLILO', 'name' => 'Sukolilo'],
                ['id' => 'TANDES', 'name' => 'Tandes'],
                ['id' => 'SAMBIKEREP', 'name' => 'Sambikerep'],
                ['id' => 'DUKUH_PAKIS', 'name' => 'Dukuh Pakis'],
                ['id' => 'WIYUNG', 'name' => 'Wiyung'],
                ['id' => 'KENJERAN', 'name' => 'Kenjeran'],
            ],
            // Kota Semarang
            'KOTA_SEMARANG' => [
                ['id' => 'SEMARANG_TENGAH', 'name' => 'Semarang Tengah'],
                ['id' => 'SEMARANG_BARAT', 'name' => 'Semarang Barat'],
                ['id' => 'SEMARANG_TIMUR', 'name' => 'Semarang Timur'],
                ['id' => 'SEMARANG_UTARA', 'name' => 'Semarang Utara'],
                ['id' => 'SEMARANG_SELATAN', 'name' => 'Semarang Selatan'],
                ['id' => 'CANDISARI', 'name' => 'Candisari'],
                ['id' => 'GAJAHMUNGKUR', 'name' => 'Gajahmungkur'],
                ['id' => 'BANYUMANIK', 'name' => 'Banyumanik'],
                ['id' => 'TEMBALANG', 'name' => 'Tembalang'],
                ['id' => 'PEDURUNGAN', 'name' => 'Pedurungan'],
                ['id' => 'NGALIYAN', 'name' => 'Ngaliyan'],
            ],
            // Kota Yogyakarta
            'KOTA_YOGYAKARTA' => [
                ['id' => 'DANUREJAN', 'name' => 'Danurejan'],
                ['id' => 'GEDONGTENGEN', 'name' => 'Gedongtengen'],
                ['id' => 'GONDOMANAN', 'name' => 'Gondomanan'],
                ['id' => 'KRATON', 'name' => 'Kraton'],
                ['id' => 'MANTRIJERON', 'name' => 'Mantrijeron'],
                ['id' => 'MERGANGSAN', 'name' => 'Mergangsan'],
                ['id' => 'NGAMPILAN', 'name' => 'Ngampilan'],
                ['id' => 'PAKUALAMAN', 'name' => 'Pakualaman'],
                ['id' => 'TEGALREJO', 'name' => 'Tegalrejo'],
                ['id' => 'UMBULHARJO', 'name' => 'Umbulharjo'],
                ['id' => 'WIROBRAJAN', 'name' => 'Wirobrajan'],
                ['id' => 'KOTAGEDE', 'name' => 'Kotagede'],
            ],
            // Kota Medan
            'KOTA_MEDAN' => [
                ['id' => 'MEDAN_KOTA', 'name' => 'Medan Kota'],
                ['id' => 'MEDAN_BARAT', 'name' => 'Medan Barat'],
                ['id' => 'MEDAN_TIMUR', 'name' => 'Medan Timur'],
                ['id' => 'MEDAN_UTARA', 'name' => 'Medan Utara'],
                ['id' => 'MEDAN_SELATAN', 'name' => 'Medan Selatan'],
                ['id' => 'MEDAN_PETISAH', 'name' => 'Medan Petisah'],
                ['id' => 'MEDAN_SUNGGAL', 'name' => 'Medan Sunggal'],
                ['id' => 'MEDAN_HELVETIA', 'name' => 'Medan Helvetia'],
            ],
        ];

        // Generic fallback districts if specific regency list not hardcoded
        if (!isset($data[$regencyId])) {
            return [
                ['id' => $regencyId . '_KEC_PUSAT', 'name' => 'Kecamatan Pusat'],
                ['id' => $regencyId . '_KEC_BARAT', 'name' => 'Kecamatan Barat'],
                ['id' => $regencyId . '_KEC_TIMUR', 'name' => 'Kecamatan Timur'],
                ['id' => $regencyId . '_KEC_UTARA', 'name' => 'Kecamatan Utara'],
                ['id' => $regencyId . '_KEC_SELATAN', 'name' => 'Kecamatan Selatan'],
            ];
        }

        return $data[$regencyId];
    }

    /**
     * Get villages (kelurahan / desa) and their postal code by district ID.
     */
    public static function getVillages(string $districtId): array
    {
        $data = [
            // Gambir
            'GAMBIR' => [
                ['id' => 'GAMBIR_KEL', 'name' => 'Gambir', 'postal_code' => '10110'],
                ['id' => 'KEBON_KELAPA', 'name' => 'Kebon Kelapa', 'postal_code' => '10120'],
                ['id' => 'PETOJO_UTARA', 'name' => 'Petojo Utara', 'postal_code' => '10130'],
                ['id' => 'DURI_PULO', 'name' => 'Duri Pulo', 'postal_code' => '10140'],
                ['id' => 'CIDENG', 'name' => 'Cideng', 'postal_code' => '10150'],
                ['id' => 'PETOJO_SELATAN', 'name' => 'Petojo Selatan', 'postal_code' => '10160'],
            ],
            // Tanah Abang
            'TANAH_ABANG' => [
                ['id' => 'BENDUNGAN_HILIR', 'name' => 'Bendungan Hilir', 'postal_code' => '10210'],
                ['id' => 'KARET_TENGSIN', 'name' => 'Karet Tengsin', 'postal_code' => '10220'],
                ['id' => 'KEBON_MELATI', 'name' => 'Kebon Melati', 'postal_code' => '10230'],
                ['id' => 'KEBON_KACANG', 'name' => 'Kebon Kacang', 'postal_code' => '10240'],
                ['id' => 'KAMPUNG_BALI', 'name' => 'Kampung Bali', 'postal_code' => '10250'],
                ['id' => 'PETAMBURAN', 'name' => 'Petamburan', 'postal_code' => '10260'],
                ['id' => 'GELORA', 'name' => 'Gelora', 'postal_code' => '10270'],
            ],
            // Kebayoran Baru
            'KEBAYORAN_BARU' => [
                ['id' => 'SELONG', 'name' => 'Selong', 'postal_code' => '12110'],
                ['id' => 'GUNUNG', 'name' => 'Gunung', 'postal_code' => '12120'],
                ['id' => 'KRAMAT_PELA', 'name' => 'Kramat Pela', 'postal_code' => '12130'],
                ['id' => 'GANDARIA_UTARA', 'name' => 'Gandaria Utara', 'postal_code' => '12140'],
                ['id' => 'CIPETE_UTARA', 'name' => 'Cipete Utara', 'postal_code' => '12150'],
                ['id' => 'PULO', 'name' => 'Pulo', 'postal_code' => '12160'],
                ['id' => 'MELAWAI', 'name' => 'Melawai', 'postal_code' => '12160'],
                ['id' => 'PETOGOGAN', 'name' => 'Petogogan', 'postal_code' => '12170'],
                ['id' => 'RAWA_BARAT', 'name' => 'Rawa Barat', 'postal_code' => '12180'],
                ['id' => 'SENAYAN', 'name' => 'Senayan', 'postal_code' => '12190'],
            ],
            // Setiabudi
            'SETIABUDI' => [
                ['id' => 'SETIABUDI_KEL', 'name' => 'Setiabudi', 'postal_code' => '12910'],
                ['id' => 'KARET', 'name' => 'Karet', 'postal_code' => '12920'],
                ['id' => 'KARET_SEMANGGI', 'name' => 'Karet Semanggi', 'postal_code' => '12930'],
                ['id' => 'KARET_KUNINGAN', 'name' => 'Karet Kuningan', 'postal_code' => '12940'],
                ['id' => 'KUNINGAN_TIMUR', 'name' => 'Kuningan Timur', 'postal_code' => '12950'],
                ['id' => 'MENTENG_ATAS', 'name' => 'Menteng Atas', 'postal_code' => '12960'],
                ['id' => 'PASAR_MANGGIS', 'name' => 'Pasar Manggis', 'postal_code' => '12970'],
                ['id' => 'GUNTUR', 'name' => 'Guntur', 'postal_code' => '12980'],
            ],
            // Coblong (Bandung)
            'COBLONG' => [
                ['id' => 'DAGO', 'name' => 'Dago', 'postal_code' => '40135'],
                ['id' => 'LEBAKSILIWANGI', 'name' => 'Lebak Siliwangi', 'postal_code' => '40132'],
                ['id' => 'LEBAKGEDE', 'name' => 'Lebak Gede', 'postal_code' => '40132'],
                ['id' => 'SEKELOA', 'name' => 'Sekeloa', 'postal_code' => '40134'],
                ['id' => 'SADANG_SERANG', 'name' => 'Sadang Serang', 'postal_code' => '40133'],
                ['id' => 'CIPAGANTI', 'name' => 'Cipaganti', 'postal_code' => '40131'],
            ],
            // Serpong (Tangsel)
            'SERPONG' => [
                ['id' => 'BSD_CITY', 'name' => 'Lengkong Gudang (BSD)', 'postal_code' => '15310'],
                ['id' => 'SERPONG_KEL', 'name' => 'Serpong', 'postal_code' => '15311'],
                ['id' => 'RAWA_BUNTU', 'name' => 'Rawa Buntu', 'postal_code' => '15318'],
                ['id' => 'BUARAN', 'name' => 'Buaran', 'postal_code' => '15310'],
                ['id' => 'CIATER', 'name' => 'Ciater', 'postal_code' => '15310'],
            ],
            // Tegalsari (Surabaya)
            'TEGALSARI' => [
                ['id' => 'TEGALSARI_KEL', 'name' => 'Tegalsari', 'postal_code' => '60262'],
                ['id' => 'DR_SOETOMO', 'name' => 'Dr. Soetomo', 'postal_code' => '60264'],
                ['id' => 'KEPATIHAN', 'name' => 'Keputran', 'postal_code' => '60265'],
                ['id' => 'WONOREJO_SBY', 'name' => 'Wonorejo', 'postal_code' => '60263'],
                ['id' => 'KEDUNGDORO', 'name' => 'Kedungdoro', 'postal_code' => '60261'],
            ],
        ];

        // Generic fallback villages if specific district not in dictionary
        if (!isset($data[$districtId])) {
            return [
                ['id' => $districtId . '_KEL_1', 'name' => 'Kelurahan / Desa 1', 'postal_code' => '10001'],
                ['id' => $districtId . '_KEL_2', 'name' => 'Kelurahan / Desa 2', 'postal_code' => '10002'],
                ['id' => $districtId . '_KEL_3', 'name' => 'Kelurahan / Desa 3', 'postal_code' => '10003'],
            ];
        }

        return $data[$districtId];
    }
}
