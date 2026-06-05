<?php
// includes/footer.php
?>
        </main>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/persian-datepicker/1.2.0/js/persian-datepicker.min.js"></script>
    <script>
        // فعال‌سازی تاریخ شمسی
        $(document).ready(function() {
            $('.datepicker').persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                observer: true,
                calendar: { persian: { locale: 'fa' } }
            });
        });
    </script>
</body>
</html>