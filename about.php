<?php
// FoodBridge — about page
$pageTitle = 'About';
include __DIR__ . '/includes/header.php';
$baseUrl = '/FoodBridge';
?>

<section class="fb-hero p-4 p-md-5 mb-5">
    <h1 class="display-6 fw-bold mb-3">About FoodBridge</h1>
    <p class="lead mb-0">
        Every day, good food is thrown away while people nearby go hungry.
        FoodBridge exists to close that gap — one shared meal at a time.
    </p>
</section>

<section class="mb-5">
    <div class="row g-4">
        <div class="col-md-6">
            <h2 class="fb-section-title h4">The problem</h2>
            <p class="text-muted">
                Restaurants, bakeries, caterers and events regularly end the day with surplus food that is
                still fresh and safe to eat. At the same time, community kitchens, shelters and low-income
                families struggle to put enough food on the table. The food and the need exist in the same
                city — often on the same street. What is missing is a simple bridge between them.
            </p>
        </div>
        <div class="col-md-6">
            <h2 class="fb-section-title h4">What we do</h2>
            <p class="text-muted">
                FoodBridge is that bridge. Donors post surplus food with a pickup location and expiry time.
                Receivers browse what is available, send a request, and collect the food before it goes to
                waste. The platform is free for both sides, and every donation is tracked from posting to
                completed pickup.
            </p>
        </div>
    </div>
</section>

<section class="mb-5">
    <h2 class="fb-section-title text-center mb-4">What we stand for</h2>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card card-donation h-100">
                <div class="card-body text-center">
                    <div style="font-size: 2.2rem;">&#9851;</div>
                    <h3 class="h6 fw-bold mt-2">Less waste</h3>
                    <p class="text-muted small mb-0">Food that is eaten instead of landfilled saves money and cuts methane emissions.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-donation h-100">
                <div class="card-body text-center">
                    <div style="font-size: 2.2rem;">&#129368;</div>
                    <h3 class="h6 fw-bold mt-2">Fewer empty plates</h3>
                    <p class="text-muted small mb-0">Surplus food reaches community kitchens, shelters and families who need it — fast, while it is still fresh.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-donation h-100">
                <div class="card-body text-center">
                    <div style="font-size: 2.2rem;">&#129309;</div>
                    <h3 class="h6 fw-bold mt-2">Stronger communities</h3>
                    <p class="text-muted small mb-0">Local businesses and neighbours help each other directly. Trust grows with every completed pickup.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="fb-cta p-4 p-md-5 text-center">
    <h2 class="fw-bold mb-2">Be part of the bridge.</h2>
    <p class="mb-4">Whether you have food to give or people to feed, there is a place for you here.</p>
    <a class="btn btn-light btn-lg" href="<?= $baseUrl ?>/auth/register.php">Join FoodBridge</a>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
