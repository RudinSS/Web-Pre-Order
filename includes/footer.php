</div> <div id="imageModal" class="modal">
        <div class="modal-content">
            <span id="closeImageModal" class="close-btn">&times;</span>
            <img id="modalImage" src="" style="width: 100%; max-height: 80vh; object-fit: contain;">
        </div>
    </div>
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 85%;
            /* NILAI YANG DISESUAIKAN LEBIH KECIL */
            max-width: 400px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 8px;
        }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .image-link { cursor: pointer; color: #007bff; text-decoration: none; }
    </style>
    
    <script>
        // Pastikan jQuery sudah ada sebelum menjalankan skrip ini
        if (window.jQuery) {
            $(document).on('click', '.image-link', function(e) {
                e.preventDefault();
                var imageUrl = $(this).data('image-url');
                if (imageUrl) {
                    $('#modalImage').attr('src', imageUrl);
                    $('#imageModal').css('display', 'block');
                }
            });

            $(document).on('click', '#closeImageModal, .modal', function() {
                $('#imageModal').css('display', 'none');
            }).on('click', '.modal-content', function(e) {
                e.stopPropagation();
            });
        }
    </script>
</body>
</html>