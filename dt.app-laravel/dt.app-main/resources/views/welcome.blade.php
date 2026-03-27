<!DOCTYPE html>
<html lang="fr">

<head>
    @include('partials.fiche.head')
</head>

<body class="index-page">

    <header id="header" class="header d-flex align-items-center fixed-top">
        @include('partials.fiche.header')
    </header>

    <main class="main">

        <!-- Hero Section -->
        <section id="hero" class="hero section dark-background">

            <img src="{{asset('templates/fiche/assets/img/bg.png')}}" alt="" data-aos="fade-in">

            <div class="container d-flex flex-column align-items-center">
                <h2 data-aos="fade-up" data-aos-delay="100">Dakar-Terminal</h2>
                <p data-aos="fade-up" data-aos-delay="200">Votre référence en terme d'activité portuaire au Sénégal</p>
                <div class="d-flex mt-4" data-aos="fade-up" data-aos-delay="300">
                    <a href="#about" class="btn-get-started">Commencer</a>
                    <a href="{{asset('templates/fiche/assets/video/video.mp4')}}" class="glightbox btn-watch-video d-flex align-items-center"><i class="bi bi-play-circle"></i><span>Regarder la vidéo</span></a>
                </div>
            </div>

        </section><!-- /Hero Section -->

        <!-- About Section -->
        <section id="about" class="about section">

            @include('partials.fiche.about')

        </section><!-- /About Section -->

        <!-- Stats Section -->
        <section id="stats" class="stats section light-background">

            @include('partials.fiche.stat')

        </section><!-- /Stats Section -->

        <!-- Services Section -->
        <section id="services" class="services section">

            @include('partials.fiche.services')

        </section><!-- /Services Section -->

        <!-- Estimation Section  -->
        <section id="estimation" class="features section">
            @include('partials.fiche.estimation')

        </section> <!--Estimation Section -->

        <!-- Digital Section  -->
        <section id="digital" class="features section">
            @include('partials.fiche.digital')

        </section> <!--Digital Section -->


        <!-- Testimonials Section -->
        <section id="testimonials" class="testimonials section dark-background">
            @include('partials.fiche.testimonial')

        </section><!-- /Testimonials Section -->

        <!-- Portfolio Section -->
        <section id="portfolio" class="portfolio section">

            @include('partials.fiche.portfolio')

        </section><!-- /Portfolio Section -->

        <!-- Team Section -->
        <section id="team" class="team section light-background">

            @include('partials.fiche.team')

        </section><!-- /Team Section -->

        <!-- Clients Section -->
        <section id="clients" class="clients section light-background">

            @include('partials.fiche.clients')

        </section><!-- /Clients Section -->

        <!-- Contact Section-->
        <section id="contact" class="contact section">

            @include('partials.fiche.contact')
        </section> <!--Contact Section-->

    </main>

    <footer id="footer" class="footer dark-background">

        @include('partials.fiche.footer')

    </footer>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Preloader -->
    <div id="preloader"></div>

    @include('partials.fiche.js')

</body>

</html>
