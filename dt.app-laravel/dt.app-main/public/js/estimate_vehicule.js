document.getElementById("estimate_vehicule").addEventListener("submit", function(e){
    e.preventDefault();

    
    var p=document.getElementById("input-1");
    var poids = p.value;
    var texte_poids = p.options[p.selectedIndex].text;

    var t=document.getElementById("input-2");
    var trajet = t.value;
    var texte_trajet = t.options[t.selectedIndex].text;

    var v=document.getElementById("input-3");
    var volume = v.value;
    var texte_volume = volume.text;

    /**
     * Véhicules
     */

        //trajet import
        if(trajet==1){
            if(poids==1){
                var prix_acconage = 25423;
                var prix_redevance = 6560;
                var prix_impression = 1400;
                var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                var prix_tva = (prix_hors_tva*18)/100;
                var total = prix_hors_tva + prix_tva;
            }
            else if(poids==2){
               var prix_acconage = 50871;
               var prix_redevance = 6560;
               var prix_impression = 1400;
               var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
               var prix_tva = (prix_hors_tva*18)/100;
               var total = prix_hors_tva + prix_tva;

            }
            else if(poids==3){
               var prix_acconage = 101791;
               var prix_redevance = 9838;
               var prix_impression = 1400;
               var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
               var prix_tva = (prix_hors_tva*18)/100;
               var total = prix_hors_tva + prix_tva;

            }
            else if(poids==4){
                  var vsr = volume/9;
                  if(vsr>3){
                     var prix_acconage = 6538;
                     var prix_redevance = 9838;
                     var prix_impression = 1400;
                     var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                     var prix_tva = (prix_hors_tva*18)/100;
                     var total = prix_hors_tva + prix_tva;
                  }
                  else if(vsr<3){

                  }
            }
        }
        //trajet import transit
        else if(trajet==2){
            if(poids==1){
               var prix_acconage = 25423;
               var prix_redevance = 1312;
               var prix_impression = 1400;
               var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
               var total = prix_hors_tva;
               
            }
            else if(poids==2){
               var prix_acconage = 50871;
               var prix_redevance = 1312;
               var prix_impression = 1400;
               var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
               var total = prix_hors_tva;
            }
            else if(poids==3){
               var prix_acconage = 101791;
               var prix_redevance = 1968;
               var prix_impression = 1400;
               var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
               var total = prix_hors_tva;
            }
            else if(poids==4){
               var vsr = volume/9;
               if(vsr>3){
                  var prix_acconage = 6538;
                  var prix_redevance = 1968;
                  var prix_impression = 1400;
                  var prix_hors_tva = prix_acconage + prix_redevance + prix_impression;
                  var total = prix_hors_tva;
               }
               else if(vsr<3){

               }
            }

            var prix_tva = 0;
        }

        var result = "TOTAL APPROXIMATIF À PAYER = "+total+
        " XOF\n\n Acconage = "+prix_acconage+
        " XOF\n Redevance ="+prix_redevance+
        " XOF\n TVA = "+prix_tva+
        " XOF\n Imprimé = "+prix_impression+
        " XOF";
        
        document.getElementById("input-4").innerHTML = result;


  




    
    
});