<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=format_import_penduduk.csv');
$output = fopen('php://output', 'w');
fputcsv($output, array('tahun', 'kelahiran', 'kematian', 'pindah_keluar', 'pindah_datang'));
fputcsv($output, array('2025', '150', '50', '20', '35'));
fclose($output);
exit;