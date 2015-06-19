<?php

namespace TFE\LibrairieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PayPalController extends Controller
{
    public function indexAction(Request $request)
    {
        //On récupère la commande via l'id en session
        $em = $this->getDoctrine()->getManager();
        $commande = $em->getRepository('TFELibrairieBundle:Commande')->find($request->getSession()->get('idCommande'));

        return $this->render('@TFELibrairie/paypal/index.html.twig', array(
                'commande'  => $commande
            ));
    }

    public function succesAction(Request $request)
    {
        //On retire la variable session idCommande
        $request->getSession()->remove('idCommande');

        return $this->render('@TFELibrairie/paypal/succes.html.twig');
    }

    public function cancelAction(Request $request)
    {
        //On récupère la commande via l'id en session
        $em = $this->getDoctrine()->getManager();
        $commande = $em->getRepository('TFELibrairieBundle:Commande')->find($request->getSession()->get('idCommande'));

        //On retire la variable session idCommande
        $request->getSession()->remove('idCommande');

        //On passe la commande en annulé
        $commande->setAnnule(1);

        return $this->render('@TFELibrairie/paypal/cancel.html.twig');
    }

    // ********** Méthode à travailler **********

    public function ipnAction(Request $request)
    {
        // permet de traiter le retour ipn de paypal

        // Email de notre compte paypal
        // Il servira à vérifier que le paiement & bien été fait vers notre nom
        $email_account = "ludovic.durand-facilitator@hotmail.be";

        // lire le formulaire provenant du système PayPal et ajouter 'cmd'
        $req = 'cmd=_notify-validate';
        foreach ($_POST as $key => $value) {
            $value = urlencode(stripslashes($value));
            $req .= "&$key=$value";
        }

        // renvoyer au système PayPal pour validation
        $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
        $fp = fsockopen ('ssl://www.sandbox.paypal.com', 80, $errno, $errstr, 30);
        $post = $request->request;
        $item_name = $post->get('item_name');
        $item_number = $post->get('item_number');
        $payment_status = $post->get('payment_status');
        $payment_amount = $post->get('mc_gross');
        $payment_currency = $post->get('mc_currency');
        $txn_id = $post->get('txn_id');
        $receiver_email = $post->get('receiver_email');
        $payer_email = $post->get('payer_email');
        $id_user = $post->get('custom');

        $em = $this->getDoctrine()->getManager();
        $commande = $em->getRepository('TFELibrairieBundle:Commande')->find($request->getSession('idCommande'));

        if (!$fp) {
            // Erreur http
        } else {
            fputs ($fp, $header . $req);
            while (!feof($fp)) {
                $res = fgets ($fp, 1024);
                if (strcmp ($res, "VERIFIED") == 0) {
                    // transaction valide
                    // Vérifier que payment_status à la valeur Completed
                    if ($payment_status == "COMPLETED") {
                        //Vérifier que le paiement a bien été fait sur notre compte
                        if ( $email_account == $receiver_email) {
                            // Vérifier que le montant payé est égale aux montants de la commande
                            if ($payment_amount == $commande->getTotalLigneCommandeTTC()) {
                                $commande->setEnAttente(1);
                                $commande->setEnvoye(1);
                            } else {
                                $commande->setAnnule(1);
                            }

                        }
                    }
                }
                else if (strcmp ($res, "INVALID") == 0) {
                    $commande->setEnAttente(0);
                    $commande->setEnvoye(1);
                }
            }
            fclose ($fp);
            $request->getSession()->remove('idCommande');
        }
        $em->flush();

        return $this->render('@TFELibrairie/paypal/ipn.html.twig');
    }
} 