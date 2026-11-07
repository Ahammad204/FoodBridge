</main>
<footer class="fb-footer mt-5">
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-md-4">
                <h6>FoodBridge</h6>
                <p class="mb-0 small">Connecting food donors with people who need it. Less waste, fewer empty plates.</p>
            </div>
            <div class="col-md-4">
                <h6>Quick links</h6>
                <ul class="list-unstyled small mb-0">
                    <li><a href="<?= $baseUrl ?? BASE_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= $baseUrl ?? BASE_URL ?>/about.php">About us</a></li>
                    <li><a href="<?= $baseUrl ?? BASE_URL ?>/contact.php">Contact</a></li>
                    <li><a href="<?= $baseUrl ?? BASE_URL ?>/auth/register.php">Become a donor</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Contact</h6>
                <p class="small mb-0">
                    Dhaka, Bangladesh<br>
                    Email: hello@foodbridge.local
                </p>
            </div>
        </div>
        <hr class="border-light opacity-25">
        <p class="text-center small mb-0">&copy; <?= date('Y') ?> FoodBridge — Share food, reduce waste.</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?? BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
