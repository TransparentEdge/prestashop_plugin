<?php
namespace Transparent\TransparentEdge;

/**
 * Class PurgeCache
 *
 */
class PurgeCache
{
    /**
     * @var TransparentApi
     */
    private $api;

    /**
     * Constructor
     *
     * @param TransparentApi $api
     */
    public function __construct(TransparentApi $api)
    {
        $this->api = $api;
    }

    /**
     * Send API purge request to invalidate cache by urls
     *
     * @param array $urls
     * @return array|bool|Result\Json
     */
    public function sendPurgeRequest(array $urls = [])
    {
        $result = $this->api->execute($urls);
        return $result;
    }
}
