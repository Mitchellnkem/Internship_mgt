<?php
require_once 'includes/header.php';
?>

<div class="jumbotron text-center bg-primary text-white rounded-lg">
    <h1 class="display-4">Welcome to BIT-InternshipHub</h1>
    <p class="lead">Find meaningful internships, build your career, and connect with employers.</p>
    <hr class="my-4 bg-white">
    <p>Explore the latest opportunities, register as a student, or log in to manage your applications.</p>
    <a class="btn btn-light btn-lg mr-2" href="internship.php" role="button">Browse Internships</a>
    <a class="btn btn-outline-white btn-lg" href="register.php" role="button">Get Started</a>
</div>

<section class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Search Opportunities</h5>
                <p class="card-text">Browse internships filtered by field, location, company, and duration.</p>
                <a href="internship.php" class="btn btn-primary">View Internships</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Application Tracker</h5>
                <p class="card-text">Sign in to track applications, submit resumes, and review status updates.</p>
                <a href="login.php" class="btn btn-primary">Login</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Employer Portal</h5>
                <p class="card-text">Employers can post internships, manage candidates, and review applications.</p>
                <a href="employer/myaccount.php" class="btn btn-primary">Employer Login</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>





