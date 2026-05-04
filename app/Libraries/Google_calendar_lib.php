<?php

namespace App\Libraries;

use App\Models\Appconfig;
use CodeIgniter\HTTP\CURLRequest;
use Config\Services;

class Google_calendar_lib
{
    private $client_id;
    private $client_secret;
    private $token;
    private $appconfig;
    private $encrypter;

    public function __construct()
    {
        $this->appconfig = model(Appconfig::class);
        $settings = config(\Config\OSPOS::class)->settings;
        
        if (check_encryption()) {
            $this->encrypter = Services::encrypter();
            $this->client_id = !empty($settings['gcalendar_client_id']) ? $this->encrypter->decrypt($settings['gcalendar_client_id']) : '';
            $this->client_secret = !empty($settings['gcalendar_client_secret']) ? $this->encrypter->decrypt($settings['gcalendar_client_secret']) : '';
            $this->token = !empty($settings['gcalendar_token']) ? json_decode($this->encrypter->decrypt($settings['gcalendar_token']), true) : null;
        }
    }

    public function get_auth_url()
    {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => site_url('appointments/google_callback'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public function exchange_code($code)
    {
        $client = Services::curlrequest();
        $response = $client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'code' => $code,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => site_url('appointments/google_callback'),
                'grant_type' => 'authorization_code'
            ]
        ]);

        $token = json_decode($response->getBody(), true);
        if (isset($token['access_token'])) {
            $this->save_token($token);
            return true;
        }
        return false;
    }

    private function save_token($token)
    {
        if (isset($this->token['refresh_token']) && !isset($token['refresh_token'])) {
            $token['refresh_token'] = $this->token['refresh_token'];
        }
        $encrypted_token = $this->encrypter->encrypt(json_encode($token));
        $this->appconfig->batch_save(['gcalendar_token' => $encrypted_token]);
        $this->token = $token;
    }

    private function refresh_token()
    {
        if (empty($this->token['refresh_token'])) return false;

        $client = Services::curlrequest();
        $response = $client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'refresh_token' => $this->token['refresh_token'],
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'refresh_token'
            ]
        ]);

        $new_token = json_decode($response->getBody(), true);
        if (isset($new_token['access_token'])) {
            $this->save_token($new_token);
            return $new_token['access_token'];
        }
        return false;
    }

    private function get_access_token()
    {
        // Simple check if token is expired (Google tokens usually last 1 hour)
        // For simplicity, we could refresh more often or check the 'expires_in'
        // Let's just try to use it and refresh if we get a 401.
        return $this->token['access_token'] ?? null;
    }

    public function create_event($data)
    {
        return $this->request('POST', 'https://www.googleapis.com/calendar/v3/calendars/primary/events', $data);
    }

    public function update_event($event_id, $data)
    {
        return $this->request('PUT', "https://www.googleapis.com/calendar/v3/calendars/primary/events/$event_id", $data);
    }

    public function delete_event($event_id)
    {
        return $this->request('DELETE', "https://www.googleapis.com/calendar/v3/calendars/primary/events/$event_id");
    }

    private function request($method, $url, $body = null)
    {
        $access_token = $this->get_access_token();
        if (!$access_token) return false;

        $client = Services::curlrequest();
        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ],
                'http_errors' => false
            ];
            if ($body) $options['json'] = $body;

            $response = $client->request($method, $url, $options);
            
            if ($response->getStatusCode() == 401) {
                $access_token = $this->refresh_token();
                if ($access_token) {
                    $options['headers']['Authorization'] = 'Bearer ' . $access_token;
                    $response = $client->request($method, $url, $options);
                }
            }

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            log_message('error', 'Google Calendar API Error: ' . $e->getMessage());
            return false;
        }
    }
}