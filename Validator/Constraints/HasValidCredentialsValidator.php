<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParametersRegistry;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopSoapClientParameters;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\InvalidCredentialException;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\SoapCallException;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\UrlExplorer;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Checks\XmlChecker;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Exception\NotReachableUrlException;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Exception\InvalidSoapUrlException;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Exception\InvalidXmlException;
use Pim\Bundle\PrestashopConnectorBundle\Item\PrestashopItemStep;

/**
 * Validator for Prestashop credentials.
 *
 */
class HasValidCredentialsValidator extends ConstraintValidator
{
    /** @var WebserviceGuesser */
    protected $webserviceGuesser;

    /** @var UrlExplorer */
    protected $urlExplorer;

    /** @var XmlChecker */
    protected $xmlChecker;

    /** @var PrestashopSoapClientParametersRegistry */
    protected $clientParametersRegistry;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param UrlExplorer                         $urlExplorer
     * @param XmlChecker                          $xmlChecker
     * @param PrestashopSoapClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        UrlExplorer $urlExplorer,
        XmlChecker $xmlChecker,
        PrestashopSoapClientParametersRegistry $clientParametersRegistry
    ) {
        $this->webserviceGuesser        = $webserviceGuesser;
        $this->urlExplorer              = $urlExplorer;
        $this->xmlChecker               = $xmlChecker;
        $this->clientParametersRegistry = $clientParametersRegistry;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param \Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement $protocol   Value to validate
     * @param Constraint                                                      $constraint Constraint for validation
     *
     * @api
     */
    public function validate($protocol, Constraint $constraint)
    {
        if (!$protocol instanceof PrestashopItemStep) {
            return null;
        }

        $clientParameters = $this->clientParametersRegistry->getInstance(
            $protocol->getSoapUsername(),
            $protocol->getSoapApiKey(),
            $protocol->getPrestashopUrl(),
            $protocol->getWsdlUrl(),
            $protocol->getDefaultStoreView(),
            $protocol->getHttpLogin(),
            $protocol->getHttpPassword()
        );

        if (null === $clientParameters->isValid() || false === $clientParameters->isValid()) {
            try {
                $xml = $this->urlExplorer->getUrlContent($clientParameters);
                $this->xmlChecker->checkXml($xml);
                $webservice = $this->webserviceGuesser->getWebservice($clientParameters);
                $webservice->getStoreViewsList();
                $clientParameters->setValidation(true);
            } catch (NotReachableUrlException $e) {
                $clientParameters->setValidation(false);
                $this->context->addViolationAt(
                    'wsdlUrl',
                    $constraint->messageUrlNotReachable.' "'.$e->getMessage().'"'
                );
            } catch (InvalidSoapUrlException $e) {
                $clientParameters->setValidation(false);
                $this->context->addViolationAt(
                    'wsdlUrl',
                    $constraint->messageSoapNotValid.' "'.$e->getMessage().'"'
                );
            } catch (InvalidXmlException $e) {
                $clientParameters->setValidation(false);
                $this->context->addViolationAt('wsdlUrl', $constraint->messageXmlNotValid);
            } catch (InvalidCredentialException $e) {
                $clientParameters->setValidation(false);
                $this->context->addViolationAt('soapUsername', $constraint->messageUsername);
            } catch (SoapCallException $e) {
                $clientParameters->setValidation(false);
                $this->context->addViolationAt('soapUsername', $e->getMessage());
            } catch (\Exception $e) {
                $clientParameters->setValidation(false);
                $this->context->addViolationAt('soapUsername', $e->getMessage());
            }
        }
    }

    /**
     * Are the given parameters valid ?
     *
     * @param PrestashopSoapClientParameters $clientParameters
     *
     * @return boolean
     */
    public function areValidSoapCredentials(PrestashopSoapClientParameters $clientParameters)
    {
        if (null === $clientParameters->isValid()) {
            try {
                $this->urlExplorer->getUrlContent($clientParameters);
                $webservice = $this->webserviceGuesser->getWebservice($clientParameters);
                $webservice->getStoreViewsList();
                $clientParameters->setValidation(true);
            } catch (\Exception $e) {
                $clientParameters->setValidation(false);
            }
        }

        return $clientParameters->isValid();
    }
}
