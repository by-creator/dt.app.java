  <!--Section Title -->
  <div class="container section-title" data-aos="fade-up">
      <h2>Contact</h2>
      <p>Contactez-Nous</p>
  </div> <!--End Section Title -->

  <div class="container" data-aos="fade-up" data-aos-delay="100">

      <div class="row gy-4">
          <div class="col-lg-6 ">
              <div class="row gy-4">

                  <div class="col-lg-12">
                      <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="200">
                          <i class="bi bi-geo-alt"></i>
                          <h3>Addresse</h3>
                          <p>47, Avenue HASSAN II</p>
                      </div>
                  </div><!--End Info Item-->

                  <div class="col-md-6">
                      <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="300">
                          <i class="bi bi-telephone"></i>
                          <h3>Appelez-nous</h3>
                          <p>33-859-16-28</p>
                      </div>
                  </div> <!--End Info Item-->

                  <div class="col-md-6">
                      <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="400">
                          <i class="bi bi-envelope"></i>
                          <h3>Envoyez nous un mail</h3>
                          <p>sn004-proforma@dakar-terminal.com</p>
                      </div>
                  </div> <!--End Info Item-->

              </div>
          </div>

          <div class="col-lg-6">
              <form action="forms/contact.php" method="post" class="php-email-form" data-aos="fade-up" data-aos-delay="500">
                  <div class="row gy-4">

                      <div class="col-md-6">
                          <input type="text" name="name" class="form-control" placeholder="Votre nom" required="">
                      </div>

                      <div class="col-md-6 ">
                          <input type="email" class="form-control" name="email" placeholder="Votre adresse mail" required="">
                      </div>

                      <div class="col-md-12">
                          <input type="text" class="form-control" name="subject" placeholder="Sujet" required="">
                      </div>

                      <div class="col-md-12">
                          <textarea class="form-control" name="message" rows="4" placeholder="Message" required=""></textarea>
                      </div>

                      <div class="col-md-12 text-center">
                          <div class="loading">Loading</div>
                          <div class="error-message"></div>
                          <div class="sent-message">Your message has been sent. Thank you!</div>

                          <button type="submit">Envoyer</button>
                      </div>

                  </div>
              </form>
          </div> <!--End Contact Form-->

      </div>

  </div>