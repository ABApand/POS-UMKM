<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'config.php';


// Tampilkan error MySQL sebagai exception
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Menyimpan data ke database
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan'])) {
    $nama_supplier = $_POST['nama_supplier'];
    $tanggal = $_POST['tanggal'];
    $petugas = $_POST['petugas'];

    try {
        // Simpan ke barang_masuk
        $stmt = $conn->prepare("INSERT INTO barang_masuk (nama_supplier, tanggal, petugas) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama_supplier, $tanggal, $petugas);
        $stmt->execute();
        $barang_masuk_id = $stmt->insert_id;

        if (!empty($_POST['nama_item'])) {
            foreach ($_POST['nama_item'] as $i => $nama_item) {
                $takaran = $_POST['takaran'][$i];
                $uom = $_POST['uom'][$i];
                $jumlah_tablet = $_POST['jumlah_tablet'][$i];
                $kadaluarsa = $_POST['kadaluarsa'][$i];
                $no_rak = $_POST['no_rak'][$i];

                // Cek apakah item sudah ada di tabel produk
$cek = $conn->prepare("SELECT stok FROM produk WHERE nama_item = ? AND takaran = ? AND uom = ?");
$cek->bind_param("sss", $nama_item, $takaran, $uom);
$cek->execute();
$result = $cek->get_result();

if ($result->num_rows > 0) {
    // Update stok jika sudah ada
    $update = $conn->prepare("UPDATE produk SET stok = stok + ? WHERE nama_item = ? AND takaran = ? AND uom = ?");
    $update->bind_param("isss", $jumlah_tablet, $nama_item, $takaran, $uom);
    $update->execute();
} else {
    // Insert produk baru
    $insert = $conn->prepare("INSERT INTO produk (nama_item, takaran, uom, stok) VALUES (?, ?, ?, ?)");
    $insert->bind_param("sssi", $nama_item, $takaran, $uom, $jumlah_tablet);
    $insert->execute();
}


                // Simpan detail barang masuk
                $detail = $conn->prepare("INSERT INTO detail_barang_masuk 
                    (barang_masuk_id, nama_item, takaran, uom, jumlah_tablet, kadaluarsa, no_rak) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $detail->bind_param("isssiss", $barang_masuk_id, $nama_item, $takaran, $uom, $jumlah_tablet, $kadaluarsa, $no_rak);
                $detail->execute();
            }
        }

        echo "<script>alert('Data berhasil disimpan!'); window.location='barang_masuk.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Terjadi kesalahan: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Barang Masuk - MRS</title>
    <link rel="stylesheet" href="style.css">
    <style>
    * {
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        margin: 0;
        padding: 0;
        background-color: #f4f6f9;
        color: #333;
    }

    .container {
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-left: 270px;
        }

        .left-panel, .right-panel {
            flex: 1 1 48%;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

    h2, h3 {
        color: #2c3e50;
        margin-bottom: 20px;
    }

    .form-container {
        display: grid;
        grid-template-columns: 1fr 200px;
        gap: 20px;
        margin-bottom: 30px;
    }

    .form-left label {
        display: block;
        margin-bottom: 12px;
    }

    .form-left label span {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
    }

    input[type="text"], input[type="date"], input[type="number"] {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
    }

    .btn-simpan, .btn-tambah {
        background-color: #28a745;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 8px;
    }

    .btn-simpan:hover, .btn-tambah:hover {
        background-color: #218838;
    }

    .table-container {
        overflow-x: auto;
        margin-bottom: 40px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background-color: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    th, td {
        padding: 12px;
        border: 1px solid #e1e5ea;
        text-align: center;
    }

    th {
        background-color: #f1f3f5;
        font-weight: bold;
        color: #333;
    }

    tbody tr:hover {
        background-color: #f8f9fa;
    }

    @media screen and (max-width: 768px) {
        .main-content {
            margin-left: 0;
            padding: 15px;
        }

        .form-container {
            grid-template-columns: 1fr;
        }
    }
    .pagination-btn {
    display: inline-block;
    margin: 0 3px;
    padding: 6px 12px;
    border: 1px solid #28a745;
    border-radius: 6px;
    text-decoration: none;
    color: #28a745;
    font-weight: bold;
    background-color: #fff;
    transition: background-color 0.2s, color 0.2s;
}

.pagination-btn:hover {
    background-color: #28a745;
    color: #fff;
}

.pagination-btn.active {
    background-color: #28a745;
    color: #fff;
    pointer-events: none;
}

</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
<div class="left-panel">
    <h2>Barang Masuk</h2>

    <form method="POST">
        <div class="form-container">
                <label><span>Nama Supplier</span><input type="text" name="nama_supplier" required></label>
                <label><span>Tanggal</span><input type="date" name="tanggal" required></label>
                <label><span>Petugas</span><input type="text" name="petugas" value="<?= $_SESSION['username'] ?>" required></label>
                <button type="submit" name="simpan" class="btn-simpan">Simpan</button>
            </div>

        <div class="table-container">
            <table id="barang-table">
            <thead>
    <tr>
        <th>Nama Item</th>
        <th>Takaran</th>
        <th>UoM</th>
        <th>Jumlah</th>
        <th>Kadaluarsa</th>
        <th>No Rak</th>
        <th>Aksi</th>
    </tr>
</thead>

<tbody>
    <tr>
        <td><input type="text" name="nama_item[]"></td>
        <td><input type="text" name="takaran[]"></td>
        <td>
            <select name="uom[]">
                <option value="">-- Pilih --</option>
                <option value="tablet">Tablet</option>
                <option value="kapsul">Kapsul</option>
                <option value="botol">Sirup</option>
                <option value="sachet">Sachet</option>
                <option value="tube">Tube</option>
            </select>
        </td>
        <td><input type="number" name="jumlah_tablet[]"></td>
        <td><input type="date" name="kadaluarsa[]"></td>
        <td><input type="text" name="no_rak[]"></td>
        <td><button type="button" onclick="hapusBaris(this)">❌</button></td>
    </tr>
</tbody>

            </table>
            <button type="button" class="btn-tambah" onclick="tambahBaris()">Tambah Baris</button>
        </div>
        </div>
    </form>

    <!-- LIVE STOK PRODUK -->
    <div class="right-panel">
    <h3>📦 Daftar Stok Produk (Live)</h3>
    <table>
        <thead>
            <tr>
                <th>Nama Item</th>
                <th>Takaran</th>
                <th>UoM</th>
                <th>Stok</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $limit = 10;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $start = ($page - 1) * $limit;
            
            // Ambil total produk untuk hitung jumlah halaman
            $total_result = $conn->query("SELECT COUNT(*) as total FROM produk");
            $total_row = $total_result->fetch_assoc();
            $total_items = $total_row['total'];
            $total_pages = ceil($total_items / $limit);
            
            // Ambil data produk terbaru (berdasarkan ID atau created_at DESC)
            $stok_produk = $conn->query("SELECT * FROM produk ORDER BY id DESC LIMIT $start, $limit");
            if ($stok_produk->num_rows > 0):
                while ($row = $stok_produk->fetch_assoc()):
            ?>
            <tr>
                <td><?= htmlspecialchars($row['nama_item']) ?></td>
                <td><?= htmlspecialchars($row['takaran']) ?></td>
                <td><?= htmlspecialchars($row['uom']) ?></td>
                <td><?= htmlspecialchars($row['stok']) ?></td>
            </tr>
            <?php
                endwhile;
            else:
            ?>
            <tr><td colspan="4" style="text-align:center; color:gray;">Belum ada data stok.</td></tr>
            <?php endif; ?>
            </tbody>
</table>

<!-- Navigasi Pagination -->
<div style="text-align:center; margin-top:10px;">
<?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="pagination-btn">&laquo; Prev</a>
        <?php endif; ?>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>" class="pagination-btn <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>" class="pagination-btn">Next &raquo;</a>
        <?php endif; ?>
</div>

    </table>
</div>
</div>
</div>

<script>
function tambahBaris() {
    const table = document.getElementById('barang-table').getElementsByTagName('tbody')[0];
    const row = table.insertRow();

    const kolom = ["nama_item", "takaran", "uom", "jumlah_tablet", "kadaluarsa", "no_rak"];
    for (let i = 0; i < kolom.length; i++) {
        const cell = row.insertCell(i);
        const input = document.createElement("input");
        input.name = kolom[i] + "[]";
        input.type = (kolom[i] === "jumlah_tablet") ? "number" : (kolom[i] === "kadaluarsa") ? "date" : "text";
        cell.appendChild(input);
    }
}


function hapusBaris(btn) {
    const row = btn.closest("tr");
    const tbody = row.parentElement;
    if (tbody.rows.length > 1) {
        tbody.removeChild(row);
    } else {
        alert("Minimal satu baris harus ada.");
    }
}


function tambahBaris() {
    const table = document.getElementById('barang-table').getElementsByTagName('tbody')[0];
    const row = table.insertRow();

    const kolom = ["nama_item", "takaran", "uom", "jumlah_tablet", "kadaluarsa", "no_rak"];
    for (let i = 0; i < kolom.length; i++) {
        const cell = row.insertCell(i);
        let input;
        if (kolom[i] === "uom") {
            input = document.createElement("select");
            input.name = "uom[]";
            input.innerHTML = `
                <option value="">-- Pilih --</option>
                <option value="tablet">tablet</option>
                <option value="kapsul">kapsul</option>
                <option value="botol">botol</option>
                <option value="sachet">sachet</option>
                <option value="tube">tube</option>
            `;
        } else {
            input = document.createElement("input");
            input.name = kolom[i] + "[]";
            input.type = (kolom[i] === "jumlah_tablet") ? "number" : (kolom[i] === "kadaluarsa") ? "date" : "text";
        }
        cell.appendChild(input);
    }

    const aksiCell = row.insertCell(kolom.length);
    const btn = document.createElement("button");
    btn.type = "button";
    btn.innerHTML = "❌";
    btn.onclick = function () { hapusBaris(this); };
    aksiCell.appendChild(btn);
}

document.querySelector("form").addEventListener("submit", function(e) {
    const rows = document.querySelectorAll("#barang-table tbody tr");
    let valid = true;

    rows.forEach(row => {
        const inputs = row.querySelectorAll("input, select");
        inputs.forEach(input => {
            if (input.value.trim() === "") {
                valid = false;
                input.style.border = "2px solid red";
            } else {
                input.style.border = "1px solid #ccc";
            }
        });

        // Cek khusus UoM (dropdown harus dipilih)
        const uomSelect = row.querySelector('select[name="uom[]"]');
        if (uomSelect && uomSelect.value === "") {
            valid = false;
            uomSelect.style.border = "2px solid red";
        }
    });

    if (!valid) {
        alert("❗ Semua kolom harus diisi, termasuk pilihan UoM.");
        e.preventDefault();
    }
});

</script>

</body>
</html>
