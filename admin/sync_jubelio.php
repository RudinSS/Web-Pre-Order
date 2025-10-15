<?php 
require_once '../includes/header.php'; 
require_once '../includes/koneksi.php'; 

// --- LOGIKA PHP: Ambil semua SKU yang ada di database lokal ---
$existing_skus_arr = [];
$sql = "SELECT sku FROM products";
$result = mysqli_query($koneksi, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $existing_skus_arr[] = $row['sku'];
    }
}
$existing_skus_json = json_encode($existing_skus_arr);
// --- AKHIR LOGIKA PHP ---
?>

<style>
    .pesan { padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid transparent; }
    .pesan.sukses { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
    .pesan.gagal { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    .pesan.warning { background-color: #fff3cd; color: #856404; border-color: #ffeeba; }
    .pesan.info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: auto; }

    /* --- CSS BARU UNTUK INDIKATOR SKU --- */
    .existing-label {
        background-color: #28a745; /* Hijau */
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        margin-left: 10px;
        display: inline-block;
        vertical-align: middle;
    }
    /* --- CSS BARU UNTUK TOMBOL --- */
    #load-more-btn {
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 12px 25px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    #load-more-btn:hover {
        background-color: #0056b3;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    #load-more-btn:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
    }
    .btn-spinner {
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-top: 3px solid white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        animation: spin 0.8s linear infinite;
    }
    #product-count-info {
        margin-top: 15px;
        color: #6c757d;
        font-style: italic;
    }
</style>

<div class="content-box">
    <h1>Pilih SKU Varian untuk Diimpor</h1>
    <div id="notification-area"></div>
    <div id="product-container">
        <div id="loading-spinner" style="text-align: center; padding: 50px;">
            <div class="spinner"></div>
            <p>Mengambil 100 data pertama dari Jubelio, mohon tunggu...</p>
        </div>
    </div>
    <div id="load-more-container" style="text-align: center; margin-top: 20px; display: none;">
        <button id="load-more-btn">
            <span class="btn-text">Muat Lebih Banyak</span>
        </button>
        <p id="product-count-info"></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let jubelioDataTable;
    let selectedItems = new Set(); 
    let currentPage = 1;
    let totalProductsLoaded = 0;
    const pageSize = 100;
    // --- MEMASUKKAN DATA SKU LOKAL KE JAVASCRIPT ---
    const existingSkus = new Set(<?php echo $existing_skus_json; ?>); 
    // --- AKHIR PENAMBAHAN ---

    function loadProducts(page) {
        const loadMoreBtn = $('#load-more-btn');
        const btnText = loadMoreBtn.find('.btn-text');
        const initialSpinner = $('#loading-spinner');
        
        if (page > 1) {
            loadMoreBtn.prop('disabled', true);
            btnText.text('Memuat...');
            loadMoreBtn.prepend('<div class="btn-spinner"></div>');
        }

        fetch(`fetch_jubelio_products.php?page=${page}`)
            .then(response => {
                if (!response.ok) { return response.json().then(err => { throw new Error(err.error || 'Network response was not ok'); }); }
                return response.json();
            })
            .then(data => {
                if (!Array.isArray(data)) { throw new Error(data.error || 'Format data dari server salah.'); }

                initialSpinner.hide();

                let newRowsHTML = '';
                data.forEach(product_group => {
                     if (product_group.variants && Array.isArray(product_group.variants)) {
                        product_group.variants.forEach(variant => {
                            let variant_name = variant.item_name || 'N/A';
                            let variant_details = [];
                            if (variant.variation_values && Array.isArray(variant.variation_values)) {
                                variant.variation_values.forEach(variation => { variant_details.push(variation.value); });
                            }
                            if (variant_details.length > 0) { variant_name += ' (' + variant_details.join(', ') + ')'; }

                            const product_data = JSON.stringify({
                                sku: variant.item_code,
                                price: variant.sell_price,
                                name: variant_name,
                                jubelio_date: product_group.last_modified,
                                image_url: variant.thumbnail,
                                jubelio_item_id: variant.item_id 
                            }).replace(/'/g, "&apos;");

                            const formattedPrice = new Intl.NumberFormat('id-ID').format(variant.sell_price || 0);
                            const date = new Date(product_group.last_modified);
                            const formattedDate = date.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                            const rawTimestamp = date.getTime() / 1000;
                            
                            // --- LOGIKA PENANDA SKU LOKAL YANG TELAH DIMODIFIKASI ---
                            const isExisting = existingSkus.has(variant.item_code);
                            const existingIndicator = isExisting ? 
                                `<span class="existing-label">Sudah Ada</span>` : '';
                            // --- AKHIR LOGIKA PENANDA ---

                            newRowsHTML += `<tr>
                                    <td style="text-align: center;"><input type="checkbox" class="product-checkbox" value='${product_data}'></td>
                                    <td>${variant_name} ${existingIndicator}</td>
                                    <td>
                                        ${variant.thumbnail ? `<a href="#" class="image-link" data-image-url="${variant.thumbnail}">${variant.item_code}</a>` : (variant.item_code || 'N/A')}
                                    </td>
                                    <td data-order="${variant.sell_price || 0}">Rp ${formattedPrice}</td>
                                    <td data-order="${rawTimestamp}">${formattedDate}</td>
                                </tr>`;
                        });
                    }
                });

                if (page === 1) {
                    let tableHTML = `<form id="import-form">
                        <table id="produk-jubelio-table" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Nama Produk (Varian)</th>
                                    <th>SKU</th>
                                    <th>Harga</th>
                                    <th>Tanggal di Jubelio</th>
                                </tr>
                            </thead>
                            <tbody>${newRowsHTML}</tbody>
                        </table>
                        <button type="submit" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; font-size: 16px; cursor: pointer; border-radius: 5px; margin-top: 20px;">Impor Produk Terpilih</button>
                    </form>`;
                    $('#product-container').html(tableHTML);
                    setupDataTableAndEvents();
                } else {
                    if (newRowsHTML) {
                        jubelioDataTable.rows.add($(newRowsHTML)).draw(false); 
                    }
                }

                totalProductsLoaded += data.length;
                $('#product-count-info').text(`Menampilkan ${totalProductsLoaded} produk.`);

                if (data.length < pageSize) {
                    $('#load-more-container').hide();
                } else {
                    $('#load-more-container').show();
                    loadMoreBtn.prop('disabled', false);
                    btnText.text('Muat Lebih Banyak');
                    loadMoreBtn.find('.btn-spinner').remove();
                }
            })
            .catch(error => {
                initialSpinner.hide();
                loadMoreBtn.find('.btn-spinner').remove();
                btnText.text('Gagal Memuat');
                $('#product-container').html('<div class="pesan gagal">Gagal memuat data. Error: ' + error.message + '</div>');
            });
    }

    function setupDataTableAndEvents() {
        jubelioDataTable = $('#produk-jubelio-table').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            "pageLength": 50,
            "order": [[ 4, "desc" ]]
        });

        // --- FUNGSI UNTUK UPDATE CHECKBOX INDUK (DARI PERBAIKAN SEBELUMNYA) ---
        function updateSelectAllCheckbox() {
            var rowsOnPage = jubelioDataTable.rows({ page: 'current', search: 'applied' }).nodes();
            var totalCheckboxesOnPage = $('input.product-checkbox', rowsOnPage).length;
            var checkedCountOnPage = 0;

            if (totalCheckboxesOnPage === 0) {
                $('#selectAll').prop('checked', false).prop('indeterminate', false);
                return;
            }

            $('input.product-checkbox', rowsOnPage).each(function() {
                var currentVal = $(this).val();
                if (selectedItems.has(currentVal)) {
                    checkedCountOnPage++;
                }
            });
            
            var allChecked = (checkedCountOnPage === totalCheckboxesOnPage);
            var anyChecked = (checkedCountOnPage > 0);
            
            $('#selectAll').prop('checked', allChecked);
            $('#selectAll').prop('indeterminate', anyChecked && !allChecked);
        }
        // --- AKHIR FUNGSI CHECKBOX INDUK ---

        // --- Perilaku DataTables saat Draw (Ganti halaman, sort, filter) ---
        jubelioDataTable.on('draw.dt', function() {
            $('.product-checkbox').each(function() {
                var currentVal = $(this).val();
                if (selectedItems.has(currentVal)) {
                    $(this).prop('checked', true);
                } else {
                    $(this).prop('checked', false);
                }
            });
            updateSelectAllCheckbox();
        });

        // Handler untuk perubahan pada checkbox baris individu
        $('#produk-jubelio-table tbody').on('change', 'input.product-checkbox', function() {
            let value = $(this).val();
            if (this.checked) {
                selectedItems.add(value);
            } else {
                selectedItems.delete(value);
            }
            updateSelectAllCheckbox();
        });
        
        // --- LOGIKA SELECT ALL ---
        $('#selectAll').on('click', function(){
            var isChecked = this.checked;
            var rows = jubelioDataTable.rows({ page: 'current' }).nodes(); 
            
            $('input.product-checkbox', rows).each(function() {
                var value = $(this).val();
                $(this).prop('checked', isChecked);
                
                if (isChecked) {
                    selectedItems.add(value); 
                } else {
                    selectedItems.delete(value); 
                }
            });
            updateSelectAllCheckbox();
        });
        // --- AKHIR LOGIKA SELECT ALL ---

        $('#import-form').on('submit', function(e) {
            e.preventDefault();
            if (selectedItems.size === 0) {
                $('#notification-area').html('<div class="pesan gagal">Tidak ada produk yang dipilih.</div>');
                return;
            }
            const formData = { 'selected_products': Array.from(selectedItems) };
            const notifArea = $('#notification-area');
            const importBtn = $(this).find('button[type="submit"]');
            notifArea.html('');
            importBtn.prop('disabled', true).text('Mengimpor...');
            $.ajax({
                url: 'proses_sync.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    let notifClass = response.type || 'info';
                    notifArea.html(`<div class="pesan ${notifClass}">${response.message}</div>`);
                    if (response.type === 'sukses') {
                        selectedItems.clear();
                        jubelioDataTable.rows().nodes().to$().find('input.product-checkbox').prop('checked', false);
                        updateSelectAllCheckbox(); 
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMessage = 'Gagal menghubungi server.<br>Status: ' + jqXHR.status + ' (' + errorThrown + ')';
                    if (jqXHR.responseText) {
                        errorMessage += '<br><strong>Detail:</strong><br><div style="text-align:left; padding:5px; background:#fff; border:1px solid #ddd;">' + jqXHR.responseText + '</div>';
                    }
                    notifArea.html(`<div class="pesan gagal">${errorMessage}</div>`);
                },
                complete: function() {
                    importBtn.prop('disabled', false).text('Impor Produk Terpilih');
                }
            });
        });
    }

    loadProducts(currentPage);

    $('#load-more-btn').on('click', function() {
        currentPage++;
        loadProducts(currentPage);
    });
});
</script>

<?php 
require_once '../includes/footer.php'; 
?>