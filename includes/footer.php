    </main>

    <footer class="page-footer font-small default-color-dark pt-4 mt-5">
        <div class="container text-center text-md-left">
            <div class="row">
                <div class="col-md-6 col-lg-4 mb-4">
                    <h6 class="text-uppercase font-weight-bold">
                        <i class="fas fa-graduation-cap mr-2"></i>BIT-InternshipHub
                    </h6>
                    <hr class="deep-purple accent-2 mb-4 mt-0 d-inline-block mx-auto" style="width: 60px;">
                    <p>Connecting students with quality internship opportunities and employer partners.</p>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <h6 class="text-uppercase font-weight-bold">Quick Links</h6>
                    <hr class="deep-purple accent-2 mb-4 mt-0 d-inline-block mx-auto" style="width: 60px;">
                    <p><a href="index.php" class="text-white">Home</a></p>
                    <p><a href="internship.php" class="text-white">Internships</a></p>
                    <p><a href="register.php" class="text-white">Register</a></p>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <h6 class="text-uppercase font-weight-bold">Contact</h6>
                    <hr class="deep-purple accent-2 mb-4 mt-0 d-inline-block mx-auto" style="width: 60px;">
                    <p><i class="fas fa-home mr-3"></i> Remote, On-site Campus & Anywhere</p>
                    <p><i class="fas fa-envelope mr-3"></i> support@bitinternshiphub.com</p>
                    <p><i class="fas fa-phone mr-3"></i> +1 234 567 8900</p>
                </div>
            </div>
        </div>
        <div class="footer-copyright text-center py-3">
            © <?= date('Y') ?> BIT InternshipHub. || Cy3rch311 || All rights reserved.
        </div>
    </footer>

    <script type="text/javascript" src="js/jquery-3.4.1.min.js"></script>
    <script type="text/javascript" src="js/popper.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/mdb.min.js"></script>
    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            $('[data-toggle="tooltip"]').tooltip();
            $('a[href^="#"]').on('click', function(event) {
                var target = $(this.getAttribute('href'));
                if(target.length) {
                    event.preventDefault();
                    $('html, body').stop().animate({ scrollTop: target.offset().top - 70 }, 1000);
                }
            });
        });
    </script>
</body>
</html>