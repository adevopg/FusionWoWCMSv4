<?php

use MX\MX_Controller;

use Stripe\Stripe;
use Stripe\Charge;

//API Container
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\PaymentExecution;

//API Functions
use PayPal\Api\Item;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Payment;
use PayPal\Api\Details;
use PayPal\Api\ItemList;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Exception\PayPalConnectionException;

class Donate extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->user->userArea();

        $this->load->config('config');
        $this->load->model('donate_model');
        $this->load->model('paypal_model');

        // Configura la clave secreta de Stripe
        Stripe::setApiKey($this->config->item('stripe_secret_key'));
    }

    public function index()
    {
        requirePermission("view");

        $this->template->setTitle(lang("donate_title", "donate"));

        $donate_paypal = $this->config->item('donate_paypal');

        $user_id = $this->user->getId();

        $paypal = array(
            "values" => $this->paypal_model->getDonations()
        );

        if ($this->input->post())
        {
            if ($this->input->post("donation_type") == "paypal")
            {
                $this->paypal_model->getDonate($this->input->post("data_id"));
            }
            elseif ($this->input->post("donation_type") == "stripe")
            {
                $this->stripeCharge();
            }
        }

        $data = array(
            "paypal" => $paypal,
            "user_id" => $user_id,
            "server_name" => $this->config->item('server_name'),
            "currency" => $this->config->item('donation_currency'),
            "currency_sign" => $this->config->item('donation_currency_sign'),
            'use_paypal' => (!empty($this->config->item("paypal_userid")) && !empty($this->config->item("paypal_secretpass")) && $this->config->item("use_paypal")) ? true : false,
            'use_stripe' => (!empty($this->config->item('stripe_publishable_key')) && !empty($this->config->item('stripe_secret_key')) && $this->config->item('use_stripe')) ? true : false,
            'stripe_publishable_key' => $this->config->item('stripe_publishable_key'),
            'donation_currency' =>  $this->config->item('donation_currency'),
            'stripe' => array('values' => array()) // Inicializa con un array vacío si no hay donaciones de Stripe
        );
		

        // Agrega información ficticia de Stripe a $data si se está utilizando Stripe
        if ($data['use_stripe']) {
            $data['stripe']['values'] = $this->obtenerDatosStripe(); // Reemplaza con tu lógica real
			
        }

        $output = $this->template->loadPage("donate.tpl", $data);

        // Load the page breadcrumb
        $page_data = array(
            "module" => "default",
            "headline" => breadcrumb(array(
                            "ucp" => lang("ucp"),
                            "donate" => lang("donate_panel", "donate")
                        )),
            "content" => $output
        );

        $page = $this->template->loadPage("page.tpl", $page_data);

        // Output the content
        $this->template->view($page, "modules/donate/css/donate.css", "modules/donate/js/donate.js");
    }
	
	 public function checkPaypal($id)
    {
        $this->paypal_model->check($id);
    }
	
	public function canceled()
    {
        $this->paypal_model->setCanceled($this->input->get("token"), '2');
        redirect(base_url('/donate'));
    }
	
	public function success()
    {
        $this->user->getUserData();

        $page = $this->template->loadPage("success.tpl", array('url' => $this->template->page_url));

        $this->template->box(lang("donate_thanks", "donate"), $page, true);
    }
	
	 public function error()
    {
        $data = array('msg' => $this->session->userdata('paypal_error'));

        $page = $this->template->loadPage("error.tpl", $data);

        $this->template->box(lang("donate_error", "donate"), $page, true);
    }

    private function obtenerDatosStripe()
    {
        // Consulta la base de datos para obtener los datos de stripe_donate
        $stripeDonations = $this->paypal_model->getStripeDonations();

        // Verifica si se obtuvieron datos de la base de datos
        if ($stripeDonations) {
            $stripeData = array();

            // Construye el array con los datos obtenidos de la base de datos
            foreach ($stripeDonations as $donation) {
                $stripeData[] = array(
                    'id' => $donation->id,
                    'price' => floatval($donation->price),
                    'points' => $donation->points,
                    'description' => $donation->description
                );
            }
			
			

            return $stripeData;
        } else {
            // Maneja la situación donde no se obtuvieron datos de la base de datos
            return array(); // Devuelve un array vacío si no hay datos
        }
    }

public function stripeCharge()
{
    // Manejar la lógica para realizar una donación a través de Stripe
    try {
        // Obtén los datos necesarios para el cargo
        $token = $this->input->post("stripeToken");
        $dataId = $this->input->post("data_id");

        // Obtén el monto y los puntos desde la base de datos según la ID del producto
        $donationData = $this->paypal_model->getStripeDonationData($dataId);

        // Verifica que los datos sean válidos
        if (!$donationData) {
            // Maneja el caso en el que no se obtienen datos válidos
            redirect(base_url('/donate/error2'));
        }

        $amount = max(0.50, floatval($donationData->price));
        $points = $donationData->points;

        // Realiza el cargo a través de Stripe
        $charge = Charge::create([
            'amount' => max(1, intval($amount * 100)),  // La cantidad debe ser al menos 1 centavo
            'currency' => $this->config->item('donation_currency'),
            'description' => lang("donation_description", "donate"),
            'source' => $token,
        ]);

        // Almacena la información en la tabla stripe_logs
        $stripeLogData = array(
            'user_id' => $this->user->getId(),
            'transaction_id' => $charge->id,
            'amount' => $amount,
            'currency' => $this->config->item('donation_currency'),
            'description' => lang("donation_description", "donate"),
            'amount_received' => $charge->amount_received ?? 'N/A', // Usamos 'N/A' si no está definido
            'status' => $charge->status
        );

        $this->paypal_model->insertStripeLog($stripeLogData);

        // Ahora, actualiza los puntos del usuario solo si la transacción fue exitosa
        if ($charge->status === 'succeeded') {
            $this->donate_model->giveDp($this->user->getId(), $points);
        }

        // Redirecciona a la página de éxito
        redirect(base_url('/donate/success'));
    } catch (\Stripe\Exception\CardException $e) {
        // Maneja errores de tarjeta de crédito
        redirect(base_url('/donate/canceled'));
    } catch (\Exception $e) {
        // Maneja otros errores
        redirect(base_url('/donate/error'));
    }
}
}




