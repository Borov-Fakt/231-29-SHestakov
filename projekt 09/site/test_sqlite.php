<?php
if (extension_loaded('sqlite3')) {
    echo "Расширение SQLite3 загружено.<br>";
} else {
    echo "Расширение SQLite3 НЕ загружено!<br>";
}

if (extension_loaded('pdo_sqlite')) {
    echo "Расширение PDO_Sqlite загружено.<br>";
} else {
    echo "Расширение PDO_Sqlite НЕ загружено!<br>";
}
?>