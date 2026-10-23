<?php
/**
 * Interface standardisée pour toutes les passerelles de paiement OSHDI
 */
interface PaymentGatewayInterface {
    /**
     * Initialise une transaction de paiement auprès du réseau
     * @param array $params (reference, amount, currency, payer_name, payer_email, payer_phone, item_title, etc.)
     * @return array (success, action, redirect_url, gateway_ref, instructions, etc.)
     */
    public function initiatePayment(array $params);

    /**
     * Vérifie le statut d'une transaction auprès du réseau
     * @param string $reference Référence OSHDI
     * @param string|null $gatewayRef Référence externe du réseau
     * @return array (status: 'valide'|'en_attente'|'echec', message, raw_data)
     */
    public function verifyPayment($reference, $gatewayRef = null);

    /**
     * Traite un webhook entrant (IPN) envoyé par le réseau
     * @param array $payload Données reçues en POST/JSON
     * @param array $headers En-têtes HTTP de la requête
     * @return array (success, reference, status, message)
     */
    public function handleWebhook(array $payload, array $headers = []);

    /**
     * Renvoie le nom d'affichage de la passerelle
     * @return string
     */
    public function getName();

    /**
     * Indique si la passerelle est actuellement en mode test / démo ou prête en production
     * @return array (is_configured: bool, mode: 'live'|'test'|'simulation', missing_keys: array)
     */
    public function getStatus();
}
