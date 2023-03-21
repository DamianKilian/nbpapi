<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class Nbpapi

{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Get Nbpapi TableData.
     *
     * @param array $data  Form data
     * 
     * @return array
     *
     * @throws Exception
     */
    public function getNbpapiTableData($data): array
    {
        $code = $data['code'];
        $startDate = $data['startDate']->format('Y-m-d');
        $endDate = $data['stopDate']->format('Y-m-d');

        $response = $this->httpClient->request('GET', "http://api.nbp.pl/api/exchangerates/rates/c/$code/$startDate/$endDate/?format=xml");

        if (200 !== $response->getStatusCode()) {
            throw new \Exception('Response status code is different than expected.');
        }

        $responseXml = $response->getContent();
        $responseData = simplexml_load_string($responseXml);

        $responseData = $this->arrayCast($responseData);

        if ($responseData) {
            $responseData = $this->addDiff($responseData);
            $responseDataHeaders = array_keys((array)$responseData[0]);
        }

        return [
            'responseData' => $responseData,
            'responseDataHeaders' => $responseDataHeaders,
        ];
    }

    /**
     * Cast Nbpapi TableData to array.
     *
     * @param \SimpleXMLElement $responseData  http://api.nbp.pl/ API XML response
     * 
     * @return array
     *
     */
    public function arrayCast($responseData): array
    {
        $responseData = ((array)$responseData->Rates)['Rate'];
        foreach ($responseData as &$value) {
            $value = (array)$value;
        }
        return $responseData;
    }

    /**
     * Cast Nbpapi TableData to array.
     *
     * @param array $responseData  http://api.nbp.pl/ API response
     * 
     * @return array
     *
     */
    public function addDiff($responseData): array
    {
        $previousValue = null;
        foreach ($responseData as &$val) {

            if ($previousValue) {
                $val['diffBid'] = bcsub($val['Bid'], $previousValue['Bid'], 4);
                $val['diffAsk'] = bcsub($val['Ask'], $previousValue['Ask'], 4);
            } else {
                $val['diffBid'] = 'null';
                $val['diffAsk'] = 'null';
            }
            $previousValue = $val;
        }
        return $responseData;
    }
}
