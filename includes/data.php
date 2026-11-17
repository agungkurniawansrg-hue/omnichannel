<?php
// DATA DUMMY PHASE 1 - bukan transaksi nyata. Akan diganti query MySQL di Phase 2+.

function get_divisions() {
    return [
        'managing-director' => ['name'=>'Managing Director','icon'=>'fa-user-tie','color'=>'#6f42c1','desc'=>'Pengawasan & pengambilan keputusan perusahaan','staff'=>3,'head'=>'Bambang Wicaksono','url'=>'managing-director/dashboard.php'],
        'sales-marketing'   => ['name'=>'Sales & Marketing','icon'=>'fa-bullhorn','color'=>'#0d6efd','desc'=>'Penjualan, pelanggan, quotation & booking','staff'=>12,'head'=>'Rina Kartika','url'=>'sales-marketing/dashboard.php'],
        'finance'           => ['name'=>'Finance','icon'=>'fa-wallet','color'=>'#198754','desc'=>'Invoice, pembayaran, pengeluaran & laporan','staff'=>6,'head'=>'Dewi Anggraini','url'=>'finance/dashboard.php'],
        'operasional'       => ['name'=>'Operasional','icon'=>'fa-truck-fast','color'=>'#fd7e14','desc'=>'Armada, pengemudi, jadwal & perawatan','staff'=>24,'head'=>'Hendra Saputra','url'=>'operasional/dashboard.php'],
        'human-resource'    => ['name'=>'Human Resource','icon'=>'fa-users','color'=>'#d63384','desc'=>'Karyawan, absensi, cuti & evaluasi','staff'=>4,'head'=>'Maya Puspita','url'=>'human-resource/dashboard.php'],
    ];
}

function get_company_stats() {
    return [
        ['label'=>'Total Pendapatan (Bln Ini)','value'=>rupiah(485750000),'icon'=>'fa-sack-dollar','color'=>'success','trend'=>'+12,4% vs bulan lalu'],
        ['label'=>'Transaksi Rental','value'=>'218','icon'=>'fa-file-contract','color'=>'primary','trend'=>'+8,1% vs bulan lalu'],
        ['label'=>'Total Pelanggan','value'=>'1.342','icon'=>'fa-users','color'=>'info','trend'=>'+36 pelanggan baru'],
        ['label'=>'Total Kendaraan','value'=>'96','icon'=>'fa-car-side','color'=>'warning','trend'=>'71 disewa / 25 tersedia'],
    ];
}

function get_revenue_series() {
    return ['labels'=>['Jun','Jul','Agu','Sep','Okt','Nov'],'data'=>[312,355,341,402,433,486]]; // juta rupiah
}

function get_fleet_status() {
    return ['labels'=>['Disewa','Tersedia','Servis'],'data'=>[71,20,5]];
}

function get_bookings() {
    return [
        ['code'=>'BKG-2611-0042','customer'=>'PT Nusa Logistik','vehicle'=>'Isuzu Elf Long','type'=>'Kendaraan Niaga','start'=>'18 Nov 2026','end'=>'25 Nov 2026','driver'=>true,'amount'=>28500000,'status'=>'Confirmed','channel'=>'Email'],
        ['code'=>'BKG-2611-0041','customer'=>'Andi Pratama','vehicle'=>'Toyota Innova Zenix','type'=>'Penumpang','start'=>'17 Nov 2026','end'=>'19 Nov 2026','driver'=>true,'amount'=>4200000,'status'=>'Ongoing','channel'=>'Telepon'],
        ['code'=>'BKG-2611-0040','customer'=>'Sari Wulandari','vehicle'=>'Toyota Alphard','type'=>'Penumpang','start'=>'20 Nov 2026','end'=>'22 Nov 2026','driver'=>true,'amount'=>9800000,'status'=>'Pending Payment','channel'=>'Instagram'],
        ['code'=>'BKG-2611-0039','customer'=>'CV Maju Bersama','vehicle'=>'Mitsubishi Colt Diesel','type'=>'Kendaraan Niaga','start'=>'16 Nov 2026','end'=>'30 Nov 2026','driver'=>false,'amount'=>21000000,'status'=>'Ongoing','channel'=>'Sales Visit'],
        ['code'=>'BKG-2611-0038','customer'=>'Budi Hartono','vehicle'=>'Toyota Avanza','type'=>'Penumpang','start'=>'15 Nov 2026','end'=>'16 Nov 2026','driver'=>false,'amount'=>1100000,'status'=>'Completed','channel'=>'Walk-in'],
        ['code'=>'BKG-2611-0037','customer'=>'Hotel Samudra','vehicle'=>'Hiace Premio','type'=>'Penumpang','start'=>'14 Nov 2026','end'=>'14 Nov 2026','driver'=>true,'amount'=>1750000,'status'=>'Cancelled','channel'=>'Telepon'],
    ];
}

function booking_badge($status) {
    $map = ['Confirmed'=>'primary','Ongoing'=>'info','Pending Payment'=>'warning','Completed'=>'success','Cancelled'=>'danger'];
    return isset($map[$status]) ? $map[$status] : 'secondary';
}

function get_channels() {
    // integration: 'simulation' | 'manual' | 'not_configured'. Tidak ada yang 'connected' di Phase 1.
    return [
        ['key'=>'whatsapp','name'=>'WhatsApp Business','group'=>'online','icon'=>'fa-brands fa-whatsapp','color'=>'#25d366','integration'=>'simulation','today'=>38],
        ['key'=>'instagram','name'=>'Instagram DM','group'=>'online','icon'=>'fa-brands fa-instagram','color'=>'#e1306c','integration'=>'simulation','today'=>14],
        ['key'=>'messenger','name'=>'Facebook Messenger','group'=>'online','icon'=>'fa-brands fa-facebook-messenger','color'=>'#0084ff','integration'=>'simulation','today'=>9],
        ['key'=>'livechat','name'=>'Website Live Chat','group'=>'online','icon'=>'fa-solid fa-comments','color'=>'#6f42c1','integration'=>'simulation','today'=>11],
        ['key'=>'telegram','name'=>'Telegram','group'=>'online','icon'=>'fa-brands fa-telegram','color'=>'#26a5e4','integration'=>'not_configured','today'=>0],
        ['key'=>'email','name'=>'Email','group'=>'online','icon'=>'fa-solid fa-envelope','color'=>'#dc3545','integration'=>'not_configured','today'=>7],
        ['key'=>'marketplace','name'=>'Marketplace / Platform Lain','group'=>'online','icon'=>'fa-solid fa-store','color'=>'#6c757d','integration'=>'not_configured','today'=>0],
        ['key'=>'phone','name'=>'Telepon','group'=>'offline','icon'=>'fa-solid fa-phone','color'=>'#198754','integration'=>'manual','today'=>12],
        ['key'=>'walkin','name'=>'Walk-in','group'=>'offline','icon'=>'fa-solid fa-person-walking','color'=>'#fd7e14','integration'=>'manual','today'=>4],
        ['key'=>'salesvisit','name'=>'Sales Visit','group'=>'offline','icon'=>'fa-solid fa-handshake','color'=>'#0dcaf0','integration'=>'manual','today'=>3],
        ['key'=>'manual','name'=>'Interaksi Manual','group'=>'offline','icon'=>'fa-solid fa-pen-to-square','color'=>'#6c757d','integration'=>'manual','today'=>2],
    ];
}

function integration_label($i) {
    $map = [
        'simulation'     => ['Simulasi', 'warning', 'Data dummy untuk uji lokal. Belum terhubung ke API resmi.'],
        'manual'         => ['Manual', 'secondary', 'Dicatat manual oleh staf. Tidak memakai API.'],
        'not_configured' => ['Belum Dikonfigurasi', 'light', 'API dan kredensial belum diatur.'],
        'connected'      => ['Terhubung', 'success', 'Terhubung ke API resmi.'],
    ];
    return $map[$i];
}

function get_conversations() {
    return [
        ['id'=>1,'customer'=>'Andi Pratama','channel'=>'whatsapp','status'=>'New','priority'=>'High','unread'=>2,'agent'=>'Belum ditugaskan','time'=>'09:41',
         'info'=>['id'=>'CUS-000231','phone'=>'0812-3456-7890','email'=>'andi.pratama@example.com','type'=>'Individu','company'=>'','status'=>'Pelanggan Aktif','pref'=>'SUV / MPV dengan pengemudi','note'=>'Sering sewa untuk perjalanan dinas.','bookings'=>[['BKG-2611-0041','Innova Zenix','Ongoing']],'payments'=>[['INV-2611-0031',rupiah(4200000),'Lunas']]],
         'messages'=>[['from'=>'customer','text'=>'Selamat pagi, saya mau perpanjang sewa Innova sampai tanggal 21 bisa?','time'=>'09:38'],['from'=>'customer','text'=>'Sekalian tanya tarif per hari tambahan berapa ya?','time'=>'09:41']]],
        ['id'=>2,'customer'=>'Sari Wulandari','channel'=>'instagram','status'=>'Open','priority'=>'Medium','unread'=>1,'agent'=>'Nadia (CS)','time'=>'09:12',
         'info'=>['id'=>'CUS-000412','phone'=>'0857-1122-3344','email'=>'sari.w@example.com','type'=>'Individu','company'=>'','status'=>'Prospek','pref'=>'Alphard / Vellfire','note'=>'Untuk acara pernikahan keluarga.','bookings'=>[['BKG-2611-0040','Alphard','Pending Payment']],'payments'=>[]],
         'messages'=>[['from'=>'customer','text'=>'Kak, Alphard tgl 20-22 Nov masih ada?','time'=>'08:55'],['from'=>'agent','text'=>'Halo Kak Sari, masih tersedia. Dengan pengemudi ya Kak?','time'=>'09:02'],['from'=>'customer','text'=>'Iya dengan driver. Berapa totalnya?','time'=>'09:12']]],
        ['id'=>3,'customer'=>'PT Nusa Logistik','channel'=>'email','status'=>'Pending','priority'=>'High','unread'=>0,'agent'=>'Rizky (Sales)','time'=>'Kemarin',
         'info'=>['id'=>'CUS-000087','phone'=>'021-5551-2200','email'=>'procurement@nusalogistik.example','type'=>'Korporasi','company'=>'PT Nusa Logistik','status'=>'Pelanggan Korporasi','pref'=>'Isuzu Elf / Colt Diesel','note'=>'Kontrak tahunan sedang dinegosiasi.','bookings'=>[['BKG-2611-0042','Isuzu Elf Long','Confirmed']],'payments'=>[['INV-2610-0120',rupiah(26000000),'Lunas']]],
         'messages'=>[['from'=>'customer','text'=>'Mohon kirimkan draft kontrak kerja sama 12 bulan untuk 5 unit Elf.','time'=>'Kemarin 15:20'],['from'=>'agent','text'=>'Baik Bu, draft akan kami kirim setelah review internal.','time'=>'Kemarin 16:05']]],
        ['id'=>4,'customer'=>'Budi Hartono','channel'=>'walkin','status'=>'Resolved','priority'=>'Low','unread'=>0,'agent'=>'Nadia (CS)','time'=>'15 Nov',
         'info'=>['id'=>'CUS-000305','phone'=>'0813-9988-7766','email'=>'','type'=>'Individu','company'=>'','status'=>'Pelanggan Aktif','pref'=>'Avanza / Xenia','note'=>'','bookings'=>[['BKG-2611-0038','Toyota Avanza','Completed']],'payments'=>[['INV-2611-0028',rupiah(1100000),'Lunas']]],
         'messages'=>[['from'=>'agent','text'=>'[Walk-in] Pelanggan datang menanyakan Avanza 1 hari tanpa driver. Booking dibuat langsung.','time'=>'15 Nov 10:10']]],
        ['id'=>5,'customer'=>'Hotel Samudra','channel'=>'phone','status'=>'Open','priority'=>'Medium','unread'=>0,'agent'=>'Rizky (Sales)','time'=>'Kemarin',
         'info'=>['id'=>'CUS-000140','phone'=>'031-5550-8899','email'=>'reservasi@samudra.example','type'=>'Korporasi','company'=>'Hotel Samudra','status'=>'Pelanggan Korporasi','pref'=>'Hiace untuk antar-jemput tamu','note'=>'Minta invoice bulanan.','bookings'=>[['BKG-2611-0037','Hiace Premio','Cancelled']],'payments'=>[]],
         'messages'=>[['from'=>'agent','text'=>'[Telepon masuk, 6 menit] Pelanggan membatalkan booking 14 Nov, ingin dijadwalkan ulang minggu depan. Tindak lanjut: kirim jadwal alternatif.','time'=>'Kemarin 11:30']]],
        ['id'=>6,'customer'=>'Dimas Kurniawan','channel'=>'livechat','status'=>'New','priority'=>'Low','unread'=>1,'agent'=>'Belum ditugaskan','time'=>'09:30',
         'info'=>['id'=>'CUS-000501','phone'=>'-','email'=>'dimas.k@example.com','type'=>'Individu','company'=>'','status'=>'Lead Baru','pref'=>'-','note'=>'','bookings'=>[],'payments'=>[]],
         'messages'=>[['from'=>'customer','text'=>'Halo, apakah ada paket sewa mobil + driver untuk ke Bromo?','time'=>'09:30']]],
        ['id'=>7,'customer'=>'CV Maju Bersama','channel'=>'salesvisit','status'=>'Pending','priority'=>'Medium','unread'=>0,'agent'=>'Rizky (Sales)','time'=>'14 Nov',
         'info'=>['id'=>'CUS-000199','phone'=>'0821-4455-6677','email'=>'info@majubersama.example','type'=>'Korporasi','company'=>'CV Maju Bersama','status'=>'Pelanggan Korporasi','pref'=>'Colt Diesel','note'=>'','bookings'=>[['BKG-2611-0039','Colt Diesel','Ongoing']],'payments'=>[['INV-2611-0020',rupiah(10500000),'Down Payment']]],
         'messages'=>[['from'=>'agent','text'=>'[Sales Visit] Bertemu Pak Joko. Tertarik tambah 2 unit Colt untuk Desember. Tindak lanjut: kirim quotation minggu ini.','time'=>'14 Nov 13:00']]],
        ['id'=>8,'customer'=>'Lestari Dewi','channel'=>'messenger','status'=>'Open','priority'=>'Medium','unread'=>0,'agent'=>'Nadia (CS)','time'=>'13 Nov',
         'info'=>['id'=>'CUS-000377','phone'=>'0878-2233-4455','email'=>'lestari.d@example.com','type'=>'Individu','company'=>'','status'=>'Prospek','pref'=>'Hiace','note'=>'','bookings'=>[],'payments'=>[]],
         'messages'=>[['from'=>'customer','text'=>'Sewa Hiace untuk 15 orang ke Malang berapa ya?','time'=>'13 Nov 19:02'],['from'=>'agent','text'=>'Halo Kak, mohon info tanggal keberangkatan dan titik jemput.','time'=>'13 Nov 19:10']]],
    ];
}

function get_agents() {
    return ['Belum ditugaskan','Nadia (CS)','Rizky (Sales)','Fajar (CS)'];
}
