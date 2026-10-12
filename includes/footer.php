    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-brand"><a class="footer-logo" href="index.php"><span class="brand-mark" aria-hidden="true">B</span> BIT-InternshipHub</a><p>Helping students turn their next opportunity into real experience.</p></div>
            <nav class="footer-links" aria-label="Footer navigation"><a href="index.php">Home</a><a href="internship.php">Browse internships</a><?php if(is_logged_in()): ?><a href="myaccount.php">My account</a><?php else: ?><a href="register.php">Create an account</a><?php endif; ?></nav>
        </div>
        <div class="footer-bottom"><div class="container">&copy; <?= date('Y') ?> BIT-InternshipHub <span>Built for the next generation of work.</span></div></div>
    </footer>

    <script type="text/javascript" src="js/jquery-3.4.1.min.js"></script>
    <script type="text/javascript" src="js/popper.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/mdb.min.js"></script>
    <script>
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>
