<?php
namespace Transparent\TransparentEdge;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;

use Symfony\Bridge\Monolog\Logger;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Container;
use Context;

class TransparentApi {
	
	/**
     * API request URL
     */
    const API_REQUEST_URI = 'https://api.transparentcdn.com';

	/**
     * API version
     */
    const API_VERSION = 'v1';

	/**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var Container
     */
    private $container;

	/**
     * @var Logger
     */
    private $logger;

	/**
     */
    public function __construct(
        ConfigurationInterface $configuration,
        Container $container,
		Logger $logger
    ) {
		$this->configuration = $configuration;
        $this->container = $container;
		$this->logger = $logger;
    }

	/**
     * execute
	 *
	 * @param array $urls
     */
    public function execute(array $urls = []): void
    {
		$this->purgeAll($urls);
	}

	/**
     * purgeAll
	 *
	 * @param array $urls
     */
    public function purgeAll(array $urls = []): void
    {
		$purgeAll = false;
		if(!$urls){
			$purgeAll = true;
			$urls = [Context::getContext()->shop->getBaseURL(true)];
		}
		$token = $this->getToken();
		if($token){
			$uri = 'companies/' . $this->configuration->get('TRANSPARENT_CDN_COMPANY_ID') . '/invalidate/';
			$reqRarams = [
				'headers' => [ 
					'Authorization' => 'Bearer ' . $token,
					'Content-Type' => 'application/json'
				],
				'body' => json_encode(['urls' => $urls])				
			];
			$response = $this->doRequest($uri, $reqRarams, HttpRequest::HTTP_METHOD_POST);

			if($response->getStatusCode() == 200){
				$status = $response->getStatusCode(); // 200 status code
				$responseBody = $response->getBody();
				$responseContent = json_decode($responseBody->getContents(), true); // here you will have the API response in JSON format
				//
				if($purgeAll){
					$this->container->get('session')->getFlashBag()->add('success', 'TransparentEdge CDN cache purged.');
				}
				$this->logger->debug('TransparentEdge CDN cache purged. ' . $responseBody->getContents(), [$urls]);
			} else {
				$this->container->get('session')->getFlashBag()->add('error', 'TransparentEdge CDN cache could not be purged. Please check your settings and try it again.');
				$this->logger->error('TransparentEdge CDN cache could not be purged.');
			}
		}
	}

	/**
     * getToken
     */
    private function getToken(): string
    {
		$uri = 'oauth2/access_token/';
		$uriParams = [
			'grant_type' => 'client_credentials', 
			'client_id' => $this->configuration->get('TRANSPARENT_CDN_CLIENT_KEY'), 
			'client_secret' => $this->configuration->get('TRANSPARENT_CDN_SECRET_KEY')
		];
		$reqRarams = [];
		$response = $this->doRequest($uri . '?' . http_build_query($uriParams), $reqRarams, HttpRequest::HTTP_METHOD_POST);
		if($response->getStatusCode() == 200){
			$status = $response->getStatusCode(); // 200 status code
			$responseBody = $response->getBody();
			$responseContent = json_decode($responseBody->getContents(), true); // here you will have the API response in JSON format
			return $responseContent['access_token'];
		} else {
			$this->container->get('session')->getFlashBag()->add('error', 'Could not get TransparentEdge CDN token with actual credentials. Please check your settings and try it again.');
			$this->logger->error('Could not get TransparentEdge CDN token with actual credentials.');
		}
		return '';
	}

	 /**
     * Do API request with provided params
     *
     * @param string $uriEndpoint
     * @param array $params
     * @param string $requestMethod
     *
     * @return Response
     */
    private function doRequest(
        string $uriEndpoint,
        array $params = [],
        string $requestMethod = HttpRequest::HTTP_METHOD_GET
    ): Response {
        /** @var Client $client */
        $client = new Client([
            'base_uri' => self::API_REQUEST_URI . '/' . self::API_VERSION . '/'
        ]);

        try {
            $response = $client->request(
                $requestMethod,
                $uriEndpoint,
                $params
            );
        } catch (GuzzleException $exception) {
            /** @var Response $response */
            $response = new Response(
                $exception->getCode(),
                $exception->getMessage()
            );
			$this->logger->error('TransparentEdge API GuzzleException: [Status:' . $exception->getCode() . ' message:' . $exception->getMessage() . ']', [$exception->getCode(), $exception->getMessage()]);
        }

        return $response;
    }
}
