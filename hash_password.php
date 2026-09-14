<?php
// File untuk generate hash password
$passwords = [
    'admin' => 'admin123',
    'petugas' => 'petugas123',
    'bendahara' => 'bendahara123'
];

foreach($passwords as $user => $pass) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    echo "Password untuk $user: $pass\n";
    echo "Hash: $hash\n\n";
}

// Contoh hasil hash (akan berbeda setiap kali di-generate)
// Tapi Anda bisa langsung menggunakan hash di atas yang sudah fix
?>