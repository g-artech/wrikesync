<?php


namespace OCA\WrikeSync\Controller;

use OCA\WrikeSync\AppInfo\HttpCommunicator;
use OCA\WrikeSync\Db\ConfigParameter;
use OCA\WrikeSync\Service\ConfigParameterService;
use OCP\IRequest;
use OCP\ILogger;
use OCP\AppFramework\Controller;

class LicenseController extends Controller
{
    //Testing and development license server URL
    // private static $LICENSE_SERVER_URL = "https://licprov.breitung-dev.de";

    //Productive license server URL
    private static $LICENSE_SERVER_URL = "http://ls.wrikesync.g-ar.tech";

    private static $LICENSE_SERVER_PATH = "/v0";

    private static $LICENSE_SERVER_ENCRYPTION_METHOD = "AES128";

    private $parameterService;
    private $logger;

    public function __construct(string $AppName, IRequest $request,
                                ConfigParameterService $parameterService,
                                ILogger $Logger)
    {
        parent::__construct($AppName, $request);
        $this->parameterService = $parameterService;
        $this->logger = $Logger;
    }

    public function checkLicenseAndConfig() {
        $licenseKey = $this->parameterService->findValueForKey(ConfigParameter::$KEY_LICENSE_KEY);
        $encryptionPassword = $this->parameterService->findValueForKey(ConfigParameter::$KEY_LICENSE_ENCRYPTION_PASSWORD);

        echo "Checking license and config for license '$licenseKey' and encryption password '$encryptionPassword'.<br>";

        try {
            $this->getConfigurationForLicense($licenseKey, $encryptionPassword);
        } catch (\Exception $e) {
            echo "ERROR ON LICENSE CHECK: ".$e->getMessage()."!<br>";
        }

        $this->parameterService->updateLastRunForLicense();
    }

    private function getConfigurationForLicense($license, $encryptionPassword) {
        $url = self::$LICENSE_SERVER_URL.self::$LICENSE_SERVER_PATH."/license.php?key=$license";

        $json = $this->doHttpGet($url);

        //Only proceed if response is given
        if ($json != null && isset($json->data)) {
            $data = $json->data;

            foreach (json_decode($data) as $key => $value) {
                $decryptedValue = null;

                echo "Received config for parameter '$key' with encrypted value '$value'.<br>";

                //If a value was returned by the license server decrypt it
                if ($value != null ) {
                    $decryptedValue = $this->decryptValue($value, $encryptionPassword);
                }

                echo "Decrypted value for key '$key' to'$decryptedValue'.<br>";

                //Check if there is already a parameter with this key
                $existingParameter = $this->parameterService->findByKey($key);
                //If a parameter with this key is existing, delete it
                if ($existingParameter != null) {
                    echo "Deleting existing parameter for key '$key'.<br>";
                    $this->parameterService->delete($existingParameter->getId());
                } else {
                    echo "No existing parameter found for key '$key'.<br>";
                }
                //If the decrypted value is set, then create a new parameter for the key with the new value
                if ($decryptedValue != null) {
                    echo "Creating new parameter entry for key '$key'.<br>";
                    $this->parameterService->create($key, $decryptedValue);
                } else {
                    echo "Value for parameter with key '$key' is empty. Not creating an entry for that.<br>";
                }
            }
        } else {
            echo "Cannot check license and config via URL '$url' because response was invalid.<br>";
        }
    }

    private function decryptValue($encryptedValue, $encryptionPassword) {
        $rawValue = openssl_decrypt($encryptedValue, self::$LICENSE_SERVER_ENCRYPTION_METHOD, $encryptionPassword);
        return $rawValue;
    }

    private function doHttpGet($url) {
        $opts = array(
            'http'=>array(
                'method'=>"GET",
                'ignore_errors' => true
            )
        );

        $context = stream_context_create($opts);

        return HttpCommunicator::doHttpRequest($url, $context);
    }
}