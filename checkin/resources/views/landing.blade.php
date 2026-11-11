<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <meta name="description" content="A simpler way to manage your stay, booking, and secure check-in.">
    <title>Lockbay — A better way to arrive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="landing">
    <header class="landing-nav">
        <a class="landing-logo" href="{{ route('home') }}" aria-label="Lockbay home">
            <span class="landing-logo-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M4.5 10.2 12 4l7.5 6.2v9.3a.9.9 0 0 1-.9.9h-4.8v-6.1h-3.6v6.1H5.4a.9.9 0 0 1-.9-.9v-9.3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 10.5h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </span>
            <span>lockbay<span class="landing-logo-dot">.</span></span>
        </a>
        <nav class="landing-links" aria-label="Main navigation">
            <a href="#how-it-works">How it works</a>
            <a href="#for-hosts">For hosts</a>
        </nav>
        <div class="landing-actions">
            <a class="landing-login" href="{{ route('login') }}">Log in</a>
            <a class="landing-nav-cta" href="{{ route('register') }}">Get started <span aria-hidden="true">↗</span></a>
        </div>
    </header>

    <main>
        <section class="landing-hero">
            <div class="landing-hero-copy">
                <div class="landing-eyebrow"><span class="landing-eyebrow-dot"></span> YOUR STAY, MADE SIMPLE</div>
                <h1>A better way<br>to <span>arrive.</span></h1>
                <p class="landing-lead">Find your place, request your dates, and get checked in without the back-and-forth. The little details are already taken care of.</p>
                <div class="landing-hero-actions">
                    <a class="landing-primary" href="{{ route('register') }}">Find your stay <span aria-hidden="true">↗</span></a>
                    <a class="landing-secondary" href="#how-it-works"><span class="landing-play" aria-hidden="true">↓</span> See how it works</a>
                </div>
                <div class="landing-social-proof">
                    <div class="landing-avatar-stack" aria-hidden="true"><span>M</span><span>L</span><span>A</span><span>+</span></div>
                    <p><strong>A smoother stay starts here.</strong><br>For guests and thoughtful hosts.</p>
                </div>
            </div>

            <div class="landing-visual" aria-label="Illustration of a stay booking and smart lock">
                <div class="landing-orbit landing-orbit-one"></div>
                <div class="landing-orbit landing-orbit-two"></div>
                <div class="landing-spark landing-spark-one">✳</div>
                <div class="landing-spark landing-spark-two">✦</div>
                <div class="landing-house">
                    <div class="landing-house-sun"></div>
                    <div class="landing-house-roof"></div>
                    <div class="landing-house-body">
                        <div class="landing-house-window"></div>
                        <div class="landing-house-window second"></div>
                        <div class="landing-house-door"><span></span></div>
                    </div>
                    <div class="landing-house-ground"></div>
                    <div class="landing-plant plant-left"><i></i><i></i><i></i></div>
                    <div class="landing-plant plant-right"><i></i><i></i><i></i></div>
                </div>
                <div class="landing-float-card landing-booking-card">
                    <span class="landing-card-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5.5" width="16" height="15" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M8 3.5v4M16 3.5v4M4 10h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                    <span><small>YOUR NEXT STAY</small><strong>All set for check-in</strong></span>
                    <span class="landing-check">✓</span>
                </div>
                <div class="landing-float-card landing-lock-card">
                    <span class="landing-lock-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                    <span><small>SMART ACCESS</small><strong>Secure & ready</strong></span>
                    <span class="landing-lock-status"></span>
                </div>
                <div class="landing-visual-caption"><span></span> A little more ease, from booking to door</div>
            </div>
        </section>

        <section class="landing-featured" id="featured-stays">
            <div class="landing-featured-heading">
                <div>
                    <span class="landing-eyebrow">A FEW PLACES TO FALL FOR</span>
                    <h2>Find your kind of <span>stay.</span></h2>
                    <p>Explore welcoming places currently open for booking.</p>
                </div>
                <a class="landing-featured-more" href="{{ route('register') }}">Explore all stays <span aria-hidden="true">↗</span></a>
            </div>
            @if($listings->isNotEmpty())
                <div class="landing-stay-grid">
                    @foreach($listings as $listing)
                        <article class="landing-stay-card">
                            <a class="landing-stay-art art-{{ ($loop->index % 3) + 1 }}" href="{{ route('register') }}" aria-label="Explore {{ $listing->name }}">
                                <span class="landing-stay-badge">OPEN FOR STAYS</span>
                                <span class="landing-stay-sun"></span>
                                <span class="landing-stay-scene">
                                    <span class="landing-stay-roof"></span>
                                    <span class="landing-stay-home">
                                        <span></span><span></span><i></i>
                                    </span>
                                    <span class="landing-stay-tree tree-left"></span>
                                    <span class="landing-stay-tree tree-right"></span>
                                    <span class="landing-stay-hill"></span>
                                </span>
                                <span class="landing-stay-art-caption">A PLACE TO UNWIND</span>
                            </a>
                            <div class="landing-stay-info">
                                <div class="landing-stay-title-row">
                                    <h3>{{ $listing->name }}</h3>
                                    <span class="landing-stay-price"><strong>${{ number_format($listing->price) }}</strong> / night</span>
                                </div>
                                <p><span class="landing-stay-pin" aria-hidden="true">⌖</span> {{ $listing->availabilityLabel() }}</p>
                                <a class="landing-stay-book" href="{{ route('register') }}">Book this stay <span aria-hidden="true">↗</span></a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="landing-featured-empty">
                    <p>New stays are on their way. Check back soon to find your next getaway.</p>
                    <a class="landing-featured-more" href="{{ route('register') }}">Create an account <span aria-hidden="true">↗</span></a>
                </div>
            @endif
        </section>

        <section class="landing-trust" aria-label="Platform benefits">
            <div><span class="landing-trust-icon">⌂</span><span><strong>Stays worth settling into</strong><small>Explore welcoming places</small></span></div>
            <div><span class="landing-trust-icon">↗</span><span><strong>Simple booking requests</strong><small>Your plans, all in one place</small></span></div>
            <div><span class="landing-trust-icon">⌑</span><span><strong>Thoughtful check-in</strong><small>Secure access, less hassle</small></span></div>
        </section>

        <section class="landing-how" id="how-it-works">
            <div class="landing-section-heading">
                <span class="landing-eyebrow">A STAY THAT FLOWS</span>
                <h2>From “let’s go” to<br><span>you’re here.</span></h2>
                <p>Everything you need for a more relaxed arrival, with no guesswork along the way.</p>
            </div>
            <div class="landing-steps">
                <article class="landing-step">
                    <span class="landing-step-number">01</span>
                    <span class="landing-step-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4.5 4.5M8 10.8h5.6M10.8 8v5.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                    <h3>Find your place</h3>
                    <p>Browse available stays and pick the dates that work for you.</p>
                </article>
                <article class="landing-step">
                    <span class="landing-step-number">02</span>
                    <span class="landing-step-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.8h10a2 2 0 0 1 2 2v14.4H5V5.8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8.5 8h7M8.5 12h7M8.5 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                    <h3>Send a request</h3>
                    <p>Keep your reservations and booking updates together in one place.</p>
                </article>
                <article class="landing-step">
                    <span class="landing-step-number">03</span>
                    <span class="landing-step-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2.5" stroke="currentColor" stroke-width="1.8"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                    <h3>Arrive with ease</h3>
                    <p>Approved bookings can use secure smart-lock access during their stay.</p>
                </article>
            </div>
        </section>

        <section class="landing-host" id="for-hosts">
            <div class="landing-host-copy">
                <span class="landing-eyebrow">FOR THOUGHTFUL HOSTS</span>
                <h2>More hosting.<br><span>Less juggling.</span></h2>
                <p>Keep the important details close at hand, from your listings and booking requests to smart-lock access.</p>
                <a class="landing-host-link" href="{{ route('login') }}">Sign in as a host <span aria-hidden="true">↗</span></a>
            </div>
            <div class="landing-host-features">
                <div><span>01</span><p><strong>Manage your listings</strong><small>Update properties and availability in one place.</small></p><b aria-hidden="true">↗</b></div>
                <div><span>02</span><p><strong>Stay on top of requests</strong><small>Review guest bookings and upcoming stays.</small></p><b aria-hidden="true">↗</b></div>
                <div><span>03</span><p><strong>Keep access in check</strong><small>Manage guest access and smart-lock status.</small></p><b aria-hidden="true">↗</b></div>
            </div>
        </section>

        <section class="landing-bottom-cta">
            <div><span class="landing-eyebrow">YOUR NEXT CHAPTER STARTS HERE</span><h2>Make room for a better stay.</h2></div>
            <a class="landing-primary" href="{{ route('register') }}">Create your account <span aria-hidden="true">↗</span></a>
        </section>
    </main>
    <footer class="landing-footer">
        <a class="landing-logo" href="{{ route('home') }}"><span class="landing-logo-mark" aria-hidden="true">⌂</span><span>lockbay<span class="landing-logo-dot">.</span></span></a>
        <span>Made for easier arrivals.</span>
        <div><a href="{{ route('login') }}">Log in</a><a href="{{ route('register') }}">Get started</a></div>
    </footer>
</body>
</html>
