<?php 
require_once '../includes/header.php';
require_once '../includes/koneksi.php'; 
?>

<style>
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
    .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
    .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    .tab-container { border-bottom: 2px solid #dee2e6; display: flex; margin-bottom: 20px; }
    .tab-link { padding: 10px 20px; cursor: pointer; border: 2px solid transparent; margin-bottom: -2px; font-weight: 500; color: #495057; }
    .tab-link.active { border-color: #dee2e6 #dee2e6 #fff; border-bottom-color: #fff; color: #007bff; }
</style>

<div class="content-box">
    <h1>Manajemen Produk</h1>

    <?php if (isset($_GET['pesan'])): ?>
        <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;">
            <?php echo htmlspecialchars(urldecode($_GET['pesan'])); ?>
        </div>
    <?php endif; ?>

    <a href="sync_jubelio.php" class="btn" style="background-color:#17a2b8; color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; display: inline-block; margin-bottom: 20px;">Sinkronkan dari Jubelio</a>
    
    <div class="tab-container">
        <div class="tab-link active" data-status="aktif">Aktif</div>
        <div class="tab-link" data-status="habis">Waktu Habis</div>
    </div>

    <form id="bulk-action-form" action="proses_produk.php" method="POST">
        <div style="margin-bottom: 10px;">
            <select name="aksi" id="bulk-action-select" style="padding: 5px;">
                <option value="">Aksi Massal...</option>
                <option value="edit_massal">Edit Terpilih</option>
                <option value="hapus_massal">Hapus Terpilih</option>
            </select>
            <button type="button" id="apply-bulk-action" style="padding: 5px 10px;">Terapkan</button>
        </div>

        <table id="manajemen-produk-table" class="display" style="width:100%;">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>SKU</th>
                    <th>Brand</th>
                    <th>Harga</th>
                    <th>Tgl Ditambahkan</th>
                    <th>Batas Waktu PO</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT p.id, p.sku, p.base_price, p.image_url, b.brand_name, p.created_at, p.deadline_po 
                        FROM products p 
                        LEFT JOIN brands b ON p.brand_id = b.id 
                        ORDER BY p.id DESC";
                $result = mysqli_query($koneksi, $sql);
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $is_expired = $row['deadline_po'] && strtotime($row['deadline_po']) < time();
                        $status = $is_expired ? 'habis' : 'aktif';

                        echo "<tr data-status='" . $status . "'>";
                        echo "<td style='text-align: center;'><input type='checkbox' class='product-checkbox' value='" . $row['id'] . "'></td>";
                        echo "<td>";
                        if (!empty($row['image_url'])) {
                            echo "<a href='#' class='image-link' data-image-url='" . htmlspecialchars($row['image_url']) . "'>" . htmlspecialchars($row['sku']) . "</a>";
                        } else {
                            echo htmlspecialchars($row['sku']);
                        }
                        echo "</td>";
                        echo "<td>" . htmlspecialchars($row['brand_name'] ?? 'N/A') . "</td>";
                        echo "<td data-order='" . $row['base_price'] . "'>Rp " . number_format($row['base_price'], 0, ',', '.') . "</td>";
                        echo "<td data-order='" . strtotime($row['created_at']) . "'>" . date("d M Y", strtotime($row['created_at'])) . "</td>";
                        
                        $deadline_text = $row['deadline_po'] ? date("d M Y, H:i", strtotime($row['deadline_po'])) : 'Tanpa Batas';
                        echo "<td data-order='" . ($row['deadline_po'] ? strtotime($row['deadline_po']) : 0) . "' style='color: " . ($is_expired ? '#dc3545' : '#28a745') . "; font-weight: bold;'>" . $deadline_text . "</td>";
                        
                        echo "<td><a href='form_edit_produk.php?id=" . $row['id'] . "'>Edit</a></td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </form>
</div>

<div id="edit-massal-modal" class="modal">
    <div class="modal-content">
        <span id="close-modal-btn" class="close-btn">&times;</span>
        <h2>Edit Produk Terpilih</h2>
        <p>Isi field yang ingin diubah. Kosongkan field jika tidak ingin mengubah nilainya.</p>
        <form id="edit-massal-form" action="proses_produk.php" method="POST">
            <input type="hidden" name="aksi" value="edit_massal_save">
            <div style="margin-bottom: 15px;">
                <label>Ubah Brand menjadi:</label>
                <select name="bulk_brand_id" style="width: 100%; padding: 8px;">
                    <option value="">-- Jangan Ubah --</option>
                    <?php
                    $sql_brands = "SELECT id, brand_name FROM brands ORDER BY brand_name";
                    $result_brands = mysqli_query($koneksi, $sql_brands);
                    while ($row_brand = mysqli_fetch_assoc($result_brands)) {
                        echo "<option value='" . $row_brand['id'] . "'>" . htmlspecialchars($row_brand['brand_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label>Ubah Batas Waktu PO menjadi:</label>
                <input type="datetime-local" name="bulk_deadline_po" style="width: 100%; padding: 8px;">
            </div>
            <button type="submit" style="background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">Simpan Perubahan</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#manajemen-produk-table').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
        "order": [[ 4, "desc" ]],
        'columnDefs': [{'targets': [0, 6], 'orderable': false}],
        // --- PERUBAHAN DI SINI: Menambahkan opsi "Semua" pada pilihan entri ---
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]]
    });
    var selectedIds = new Set();

    function updateSelectAllCheckbox() {
        var rowsOnPage = table.rows({ page: 'current' }).nodes();
        var allCheckedOnPage = true;
        if ($('input.product-checkbox', rowsOnPage).length === 0) { allCheckedOnPage = false; }
        $('input.product-checkbox', rowsOnPage).each(function() {
            if (!this.checked) {
                allCheckedOnPage = false;
                return false;
            }
        });
        $('#selectAll').prop('checked', allCheckedOnPage);
    }

    table.on('draw', function() {
        $('.product-checkbox').each(function() {
            var currentId = parseInt($(this).val());
            if (selectedIds.has(currentId)) {
                $(this).prop('checked', true);
            } else {
                $(this).prop('checked', false);
            }
        });
        updateSelectAllCheckbox();
    });

    $('#selectAll').on('click', function(){
        var rows = table.rows({ page: 'current' }).nodes();
        var isChecked = this.checked;
        $('input.product-checkbox', rows).each(function() {
            $(this).prop('checked', isChecked);
            var id = parseInt($(this).val());
            if (isChecked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
        });
    });

    $('#manajemen-produk-table tbody').on('change', 'input.product-checkbox', function() {
        var id = parseInt($(this).val());
        if (this.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        updateSelectAllCheckbox();
    });
    
    var modal = document.getElementById("edit-massal-modal");
    var closeModalBtn = document.getElementById("close-modal-btn");

    $('#apply-bulk-action').on('click', function() {
        var action = $('#bulk-action-select').val();
        if (selectedIds.size === 0) {
            alert('Silakan pilih minimal satu produk.');
            return;
        }
        if (action === 'hapus_massal') {
            if (confirm('Apakah Anda yakin ingin menghapus ' + selectedIds.size + ' produk yang dipilih?')) {
                submitBulkForm('hapus_massal');
            }
        } else if (action === 'edit_massal') {
            modal.style.display = "block";
        } else {
            alert('Silakan pilih aksi terlebih dahulu.');
        }
    });

    closeModalBtn.onclick = function() { modal.style.display = "none"; }
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    $('#edit-massal-form').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        $(form).find('input[type="hidden"][name="selected_ids[]"]').remove();
        selectedIds.forEach(function(id) {
            $(form).append(`<input type="hidden" name="selected_ids[]" value="${id}">`);
        });
        form.submit();
    });

    function submitBulkForm(action) {
        var form = $('#bulk-action-form');
        form.find('input[type="hidden"][name="selected_ids[]"]').remove();
        selectedIds.forEach(function(id) {
            form.append(`<input type="hidden" name="selected_ids[]" value="${id}">`);
        });
        form.find('#bulk-action-select').val(action);
        form.submit();
    }
    
    // Logika filter tab dikembalikan seperti semula
    $('.tab-link').on('click', function() {
        $('.tab-link').removeClass('active');
        $(this).addClass('active');
        var status = $(this).data('status');
        
        $.fn.dataTable.ext.search.pop();
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var rowStatus = $(table.row(dataIndex).node()).data('status');
                return rowStatus === status;
            }
        );
        table.draw();
        $('#selectAll').prop('checked', false);
    });

    // Otomatis klik tab aktif saat halaman dimuat
    $('.tab-link.active').click();
});
</script>

<?php 
require_once '../includes/footer.php';
?>