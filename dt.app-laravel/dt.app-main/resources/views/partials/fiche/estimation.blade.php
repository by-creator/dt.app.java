<!-- Section Title -->
<div class="container section-title" data-aos="fade-up">
    <h2>Estimations</h2>
    <p>Estimation des coûts approximatifs<br></p>
</div><!--End Section Title -->

<div class="container">

    <ul class="nav nav-tabs row  d-flex" data-aos="fade-up" data-aos-delay="100">
        <li class="nav-item col-3">
            <a class="nav-link active show" data-bs-toggle="tab" data-bs-target="#features-tab-1">
                <i class="fa-solid fa-boxes-stacked"></i>
                <h4 class="d-none d-lg-block">Conteneurs</h4>
            </a>
        </li>
        <li class="nav-item col-3">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-2">
                <i class="fa-solid fa-car"></i>
                <h4 class="d-none d-lg-block">Véhicules</h4>
            </a>
        </li>

    </ul> <!--End Tab Nav-->

    <div class="tab-content" data-aos="fade-up" data-aos-delay="200">

        <div class="tab-pane fade active show" id="features-tab-1">
            <div class="row">
                <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">
                        <div class="col-lg-12">
                            <form action="#" class="row" id="estimate_conteneur" method="post" class="php-email-form" data-aos="fade-up" data-aos-delay="500">
                                <div class="row gy-4">

                                    <div class="col-md-6">
                                        <select name="trajet" id="input-6" class="form-control">
                                            <option value="disabled" disabled selected>Sélectionner le Trajet</option>
                                            <option value="1">Import / Export</option>
                                            <option value="2">Transit</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 ">
                                        <select name="marchandise" id="input-5" class="form-control">
                                            <option value="0" disabled selected>Type de Marchandise</option>
                                            <option value="1">Coton (Senegal - Mali)</option>
                                            <option value="2">Produit de Standard</option>
                                            <option value="3">Produit de base</option>
                                            <option value="4">Produit frigorifique</option>
                                            <option value="5">Produit dangereuse</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <select name="nombre_de_pieds" id="input-7" class="form-control">
                                            <option value="disabled" disabled selected>Nombre de pieds</option>
                                            <option value="20">20 pieds</option>
                                            <option value="40">40 pieds</option>
                                            <option value="50">20 pieds (Speciaux)</option>
                                            <option value="60">40 pieds (Speciaux)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <textarea id="result-8" class="form-control" name="montant" rows="8" required="" disabled></textarea>
                                    </div>

                                    <div class="col-md-12 text-center">
                                        <button type="submit" class="btn btn-dark">VALIDER</button>
                                    </div>

                                </div>
                            </form>
                        </div>
                </div>
                <div class="col-lg-6 order-1 order-lg-2 text-center">
                    <img src="{{asset('img/hero_2.png')}}" alt="" class="img-fluid">
                </div>
            </div>
        </div><!--End Tab Content Item -->

        <div class="tab-pane fade" id="features-tab-2">
            <div class="row">
                <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">

                        <div class="col-lg-12">
                            <form action="#" class="row" id="estimate_vehicule" method="post" class="php-email-form" data-aos="fade-up" data-aos-delay="500">
                                <div class="row gy-4">

                                    <div class="col-md-6">
                                        <select name="poids" id="input-1" class="form-control">
                                            <option value="disabled">Sélectionner le Poids</option>
                                            <option value="1">moins de 1500</option>
                                            <option value="2">entre 1t et 3t</option>
                                            <option value="3">plus de 3t</option>
                                            <option value="4">plus de 9t</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 ">
                                        <select name="" id="input-2" class="form-control">
                                            <option value="disabled">Sélectionner le Trajet</option>
                                            <option value="1">Import</option>
                                            <option value="2">Import transit</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                    <input type="number" name="volume" value="volume" class="form-control" id="input-3" placeholder="Entrer le Volume ">

                                    </div>

                                    <div class="col-md-12">
                                        <textarea id="input-4" class="form-control" name="" rows="8" required="" disabled></textarea>
                                    </div>

                                    <div class="col-md-12 text-center">
                                        <button type="submit" class="btn btn-dark">VALIDER</button>
                                    </div>

                                </div>
                            </form>
                        </div>
                </div>
                <div class="col-lg-6 order-1 order-lg-2 text-center">
                    <img src="{{asset('img/working-5.jpg')}}" alt="" class="img-fluid">
                </div>
            </div>
        </div> <!--End Tab Content Item -->
        <!--
                    <div class="tab-pane fade" id="features-tab-3">
                        <div class="row">
                            <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">
                                <h3>Voluptatibus commodi ut accusamus ea repudiandae ut autem dolor ut assumenda</h3>
                                <p>
                                    Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate
                                    velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
                                    culpa qui officia deserunt mollit anim id est laborum
                                </p>
                                <ul>
                                    <li><i class="bi bi-check2-all"></i> <span>Ullamco laboris nisi ut aliquip ex ea commodo consequat.</span></li>
                                    <li><i class="bi bi-check2-all"></i> <span>Duis aute irure dolor in reprehenderit in voluptate velit.</span></li>
                                    <li><i class="bi bi-check2-all"></i> <span>Provident mollitia neque rerum asperiores dolores quos qui a. Ipsum neque dolor voluptate nisi sed.</span></li>
                                </ul>
                                <p class="fst-italic">
                                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore
                                    magna aliqua.
                                </p>
                            </div>
                            <div class="col-lg-6 order-1 order-lg-2 text-center">
                                <img src="{{asset('templates/fiche/assets/img/working-3.jpg')}}" alt="" class="img-fluid">
                            </div>
                        </div>
                    </div> End Tab Content Item 

                    <div class="tab-pane fade" id="features-tab-4">
                        <div class="row">
                            <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">
                                <h3>Omnis fugiat ea explicabo sunt dolorum asperiores sequi inventore rerum</h3>
                                <p>
                                    Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate
                                    velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
                                    culpa qui officia deserunt mollit anim id est laborum
                                </p>
                                <p class="fst-italic">
                                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore
                                    magna aliqua.
                                </p>
                                <ul>
                                    <li><i class="bi bi-check2-all"></i> <span>Ullamco laboris nisi ut aliquip ex ea commodo consequat.</span></li>
                                    <li><i class="bi bi-check2-all"></i> <span>Duis aute irure dolor in reprehenderit in voluptate velit.</span></li>
                                    <li><i class="bi bi-check2-all"></i> <span>Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate trideta storacalaperda mastiro dolore eu fugiat nulla pariatur.</span></li>
                                </ul>
                            </div>
                            <div class="col-lg-6 order-1 order-lg-2 text-center">
                                <img src="{{asset('templates/fiche/assets/img/working-4.jpg')}}" alt="" class="img-fluid">
                            </div>
                        </div>
                    </div>End Tab Content Item -->

    </div>

</div>