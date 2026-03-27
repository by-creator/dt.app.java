document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("estimate_conteneur").addEventListener("submit", function (e) {
    e.preventDefault();
    var marchandise = document.getElementById("input-5").value;
    var trajet = document.getElementById("input-6").value;
    var nombre_pieds = document.getElementById("input-7").value;
    var options50 = document.getElementById("input-7").querySelector("option[value='50']");
    var options60 = document.getElementById("input-7").querySelector("option[value='60']");
     options50.style.display = "none"; 
    options60.style.display = "none"; 
    var tva, taxe, frais;
    if (trajet === ""|| nombre_pieds === "") {
        document.getElementById("result-8").value = "Veuillez sélectionner des valeurs pour la marchandise, le trajet et le nombre de pieds .";
        return;
    }
    document.getElementById("input-6").addEventListener("change", function() {
        var marchandiseOption2 = document.getElementById("input-5").querySelector("option[value='2']");
        if (this.value === "2") { 
            options50.style.display = "none";
            options60.style.display = "none";
            document.getElementById("input-5").disabled = true;
        } else {
            options50.style.display = "block";
            options60.style.display = "block"; 
            document.getElementById("input-5").disabled = false; 
        }
    });
        switch (marchandise) {
            
        case "1": 
            if (trajet === "1") {
                switch (nombre_pieds) {
                    case "20":
                        var prix_acconage = 70000;
                        var prix_redevance = 18250;
                        var prix_impression = 1400;
                        var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                        var prix_tva = (prix_hors_tva*18)/100;
                        var total = prix_hors_tva + prix_tva;
                        break;
                    case "40":
                        var prix_acconage = 140000;
                        var prix_redevance = 36560;
                        var prix_impression = 1400;
                        var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                        var prix_tva = (prix_hors_tva*18)/100;
                        var total = prix_hors_tva + prix_tva;
                        break;
                    default:
                        tva = taxe = frais = 0;
                }
            } else if (trajet === "2") {
                switch (nombre_pieds) {
                        case "20":
                            var prix_acconage = 110000;
                            var prix_redevance = 18250;
                            var prix_impression = 1400;
                            var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                            var prix_tva = (prix_hors_tva*18)/100;
                            var total = prix_hors_tva + prix_tva;
                            break;
                        case "40":
                            var prix_acconage = 220000;
                            var prix_redevance = 36560;
                            var prix_impression = 1400;
                            var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                            var prix_tva = (prix_hors_tva*18)/100;
                            var total = prix_hors_tva + prix_tva;
                            break;
                        default:
                            tva = taxe = frais = 0;
                }
            }
            break;
        case "2": 
        if (trajet === "1") {
            switch (nombre_pieds) {
                case "20":
                    var prix_acconage = 155000;
                    var prix_redevance = 18250;
                    var prix_impression = 1400;
                    var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                    var prix_tva = (prix_hors_tva*18)/100;
                    var total = prix_hors_tva + prix_tva;
                    break;
                case "40":
                    var prix_acconage = 310000;
                    var prix_redevance = 36560;
                    var prix_impression = 1400;
                    var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                    var prix_tva = (prix_hors_tva*18)/100;
                    var total = prix_hors_tva + prix_tva;
                    break;
                case "50":
                        var prix_acconage = 310000;
                        var prix_redevance = 36560;
                        var prix_impression = 1400;
                        var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                        var prix_tva = (prix_hors_tva*18)/100;
                        var total = prix_hors_tva + prix_tva;
                        break;
                case "60":
                            var prix_acconage = 620000;
                            var prix_redevance = 36560;
                            var prix_impression = 1400;
                            var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                            var prix_tva = (prix_hors_tva*18)/100;
                            var total = prix_hors_tva + prix_tva;
                            break;
                default:
                    tva = taxe = frais = 0;
            }
        } else if (trajet === "2") {
            switch (nombre_pieds) {
                    case "20":
                        var prix_acconage = 310000;
                        var prix_redevance = 36560;
                        var prix_impression = 1400;
                        var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                        var prix_tva = (prix_hors_tva*18)/100;
                        var total = prix_hors_tva + prix_tva;
                        break;
                    case "40":
                        var prix_acconage = 620000;
                        var prix_redevance = 73120;
                        var prix_impression = 1400;
                        var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                        var prix_tva = (prix_hors_tva*18)/100;
                        var total = prix_hors_tva + prix_tva;
                        break;
                    default:
                        tva = taxe = frais = 0;
            }
        }
            break;
        case "3": 
        if (trajet === "1") {
            switch (nombre_pieds) {
                case "20":
                    var prix_acconage = 70000;
                    var prix_redevance = 18250;
                    var prix_impression = 1400;
                    var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                    var prix_tva = (prix_hors_tva*18)/100;
                    var total = prix_hors_tva + prix_tva;
                    break;
                case "40":
                    var prix_acconage = 140000;
                    var prix_redevance = 36560;
                    var prix_impression = 1400;
                    var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                    var prix_tva = (prix_hors_tva*18)/100;
                    var total = prix_hors_tva + prix_tva;
                    break;
                default:
                    tva = taxe = frais = 0;
            }
        } else if (trajet === "2") {
            switch (nombre_pieds) {
                case "20":
                    var prix_acconage = 310000;
                    var prix_redevance = 36560;
                    var prix_impression = 1400;
                    var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                    var prix_tva = (prix_hors_tva*18)/100;
                    var total = prix_hors_tva + prix_tva;
                    break;
                case "40":
                    var prix_acconage = 620000;
                    var prix_redevance = 73120;
                    var prix_impression = 1400;
                    var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                    var prix_tva = (prix_hors_tva*18)/100;
                    var total = prix_hors_tva + prix_tva;
                    break;
                default:
                    tva = taxe = frais = 0;
            }
        }
            break;
        case "4": 
        if (trajet === "1") {
            switch (nombre_pieds) {
                case "20":
                    var prix_acconage = 170500;
                    var prix_redevance = 18250;
                    var prix_impression = 1400;
                    var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                    var prix_tva = (prix_hors_tva*18)/100;
                    var total = prix_hors_tva + prix_tva;
                    break;
                case "40":
                    var prix_acconage = 341000;
                    var prix_redevance = 36560;
                    var prix_impression = 1400;
                    var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                    var prix_tva = (prix_hors_tva*18)/100;
                    var total = prix_hors_tva + prix_tva;
                    break;
                default:
                    tva = taxe = frais = 0;
            }
        } else if (trajet === "2") {
            switch (nombre_pieds) {
                case "20":
                    var prix_acconage = 310000;
                    var prix_redevance = 36560;
                    var prix_impression = 1400;
                    var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                    var prix_tva = (prix_hors_tva*18)/100;
                    var total = prix_hors_tva + prix_tva;
                    break;
                case "40":
                    var prix_acconage = 620000;
                    var prix_redevance = 73120;
                    var prix_impression = 1400;
                    var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                    var prix_tva = (prix_hors_tva*18)/100;
                    var total = prix_hors_tva + prix_tva;
                    break;
                default:
                    tva = taxe = frais = 0;
            }
        }
            break;
            case "5": 
            if (trajet === "1") {
                switch (nombre_pieds) {
                    case "20":
                        var prix_acconage = 170500 +170500 *50/100;
                        var prix_redevance = 18250;
                        var prix_impression = 1400;
                        var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                        var prix_tva = (prix_hors_tva*18)/100;
                        var total = prix_hors_tva + prix_tva;
                        break;
                    case "40":
                        var prix_acconage = 341000 + 341000 *50/100;
                        var prix_redevance = 36560;
                        var prix_impression = 1400;
                        var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                        var prix_tva = (prix_hors_tva*18)/100;
                        var total = prix_hors_tva + prix_tva;
                        break;
                    default:
                        tva = taxe = frais = 0;
                }
            } else if (trajet === "2") {
                switch (nombre_pieds) {
                    case "20":
                        var prix_acconage = 310000;
                        var prix_redevance = 36560;
                        var prix_impression = 1400;
                        var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                        var prix_tva = (prix_hors_tva*18)/100;
                        var total = prix_hors_tva + prix_tva;
                        break;
                    case "40":
                        var prix_acconage = 620000;
                        var prix_redevance = 73120;
                        var prix_impression = 1400;
                        var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                        var prix_tva = (prix_hors_tva*18)/100;
                        var total = prix_hors_tva + prix_tva;
                        break;
                    default:
                        tva = taxe = frais = 0;
                }
            }
            case "0": 
             if (trajet === "2") {
                switch (nombre_pieds) {
                    case "20":
                        var prix_acconage = 310000;
                        var prix_redevance = 36560;
                        var prix_impression = 1400;
                        var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                        var prix_tva = (prix_hors_tva*18)/100;
                        var total = prix_hors_tva + prix_tva;
                        break;
                    case "40":
                        var prix_acconage = 620000;
                        var prix_redevance = 73120;
                        var prix_impression = 1400;
                        var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                        var prix_tva = (prix_hors_tva*18)/100;
                        var total = prix_hors_tva + prix_tva;
                        break;
                    default:
                        tva = taxe = frais = 0;
                }
            }
        default:
            tva = taxe = frais = 0;
    }

    var result = "TOTAL APPROXIMATIF À PAYER = "+total+
    " XOF\n\n Acconage = "+prix_acconage+
    " XOF\n Redevance ="+prix_redevance+
    " XOF\n TVA = "+prix_tva+
    " XOF\n Imprimé = "+prix_impression+
    " XOF";
    document.getElementById("result-8").value = result;
});
});
  