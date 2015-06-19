<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Item;

use Symfony\Component\Validator\Constraints as Assert;
use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Pim\Bundle\PrestashopConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\PrestashopConnectorBundle\Validator\Constraints\HasValidCredentials;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParametersRegistry;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParameters;
use Pim\Bundle\PrestashopConnectorBundle\Webservice\Webservice;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Event\InvalidItemEvent;
use Akeneo\Bundle\BatchBundle\Event\EventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Prestashop item step.
 *
 * @HasValidCredentials(groups={"Execution"})
 */
abstract class PrestashopItemStep extends AbstractConfigurableStepElement implements StepExecutionAwareInterface
{
    /** @var Webservice */
    protected $webservice;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var WebserviceGuesser */
    protected $webserviceGuesser;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $defaultStoreView = Webservice::REST_DEFAULT_STORE_VIEW;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Url(groups={"Execution"})
     */
    protected $prestashopUrl;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $restApiKey;

    /** @var string */
    protected $httpLogin;

    /** @var string */
    protected $httpPassword;

    /** @var PrestashopRestClientParameters */
    protected $clientParameters;

    /** @var PrestashopRestClientParametersRegistry */
    protected $clientParametersRegistry;

    /** @var boolean */
    protected $beforeExecute = false;

    /** @var boolean */
    protected $afterConfiguration = false;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param PrestashopRestClientParametersRegistry $clientParametersRegistry
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        PrestashopRestClientParametersRegistry $clientParametersRegistry
    ) {
        $this->clientParametersRegistry = $clientParametersRegistry;
        $this->webserviceGuesser        = $webserviceGuesser;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $config)
    {
        parent::setConfiguration($config);

        if (!$this->afterConfiguration) {
            $this->afterConfigurationSet();

            $this->afterConfiguration = true;
        }
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Get fields for the twig.
     *
     * @return array
     */
    public function getConfigurationFields()
    {
        return [
            'restApiKey'   => [
                //Should be replaced by a password formType but which doesn't
                //empty the field at each edit
                'type'    => 'text',
                'options' => [
                    'required' => true,
                    'help'     => 'pim_prestashop_connector.export.restApiKey.help',
                    'label'    => 'pim_prestashop_connector.export.restApiKey.label',
                ],
            ],
            'prestashopUrl' => [
                'options' => [
                    'required' => true,
                    'help'     => 'pim_prestashop_connector.export.prestashopUrl.help',
                    'label'    => 'pim_prestashop_connector.export.prestashopUrl.label',
                ],
            ],
            'httpLogin' => [
                'options' => [
                    'required' => false,
                    'help'     => 'pim_prestashop_connector.export.httpLogin.help',
                    'label'    => 'pim_prestashop_connector.export.httpLogin.label',
                ],
            ],
            'httpPassword' => [
                'options' => [
                    'required' => false,
                    'help'     => 'pim_prestashop_connector.export.httpPassword.help',
                    'label'    => 'pim_prestashop_connector.export.httpPassword.label',
                ],
            ],
            'defaultStoreView' => [
                'options' => [
                    'required' => false,
                    'help'     => 'pim_prestashop_connector.export.defaultStoreView.help',
                    'label'    => 'pim_prestashop_connector.export.defaultStoreView.label',
                    'data'     => $this->getDefaultStoreView(),
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function getDefaultStoreView()
    {
        return $this->defaultStoreView;
    }

    /**
     * @param string $defaultStoreView
     *
     * @return PrestashopItemStep
     */
    public function setDefaultStoreView($defaultStoreView)
    {
        $this->defaultStoreView = $defaultStoreView;

        return $this;
    }

    /**
     * @return string
     */
    public function getRestApiKey()
    {
        return $this->restApiKey;
    }

    /**
     * @param string $restApiKey
     *
     * @return PrestashopItemStep
     */
    public function setRestApiKey($restApiKey)
    {
        $this->restApiKey = $restApiKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrestashopUrl()
    {
        return $this->prestashopUrl;
    }

    /**
     * @param string $prestashopUrl
     *
     * @return PrestashopItemStep
     */
    public function setPrestashopUrl($prestashopUrl)
    {
        $this->prestashopUrl = $prestashopUrl;

        return $this;
    }

    /**
     * @param string $httpLogin
     *
     * @return PrestashopItemStep
     */
    public function setHttpLogin($httpLogin)
    {
        $this->httpLogin = $httpLogin;

        return $this;
    }

    /**
     * @return string
     */
    public function getHttpLogin()
    {
        return $this->httpLogin;
    }

    /**
     * @param string $httpPassword
     *
     * @return PrestashopItemStep
     */
    public function setHttpPassword($httpPassword)
    {
        $this->httpPassword = $httpPassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getHttpPassword()
    {
        return $this->httpPassword;
    }

    /**
     * Function called before all item step execution.
     */
    protected function beforeExecute()
    {
        if ($this->beforeExecute) {
            return null;
        }

        $this->beforeExecute = true;

        $this->webservice = $this->webserviceGuesser->getWebservice($this->getClientParameters());
    }

    /**
     * Called after configuration set.
     */
    protected function afterConfigurationSet()
    {
    }

    /**
     * Get the prestashop soap client parameters.
     *
     * @return PrestashopRestClientParameters
     */
    protected function getClientParameters()
    {
        $this->clientParameters = $this->clientParametersRegistry->getInstance(
            $this->restApiKey,
            $this->prestashopUrl,
            $this->defaultStoreView,
            $this->httpLogin,
            $this->httpPassword
        );

        return $this->clientParameters;
    }

    /**
     * Add a warning based on the stepExecution.
     *
     * @param string $message
     * @param array  $messageParameters
     * @param mixed  $item
     */
    protected function addWarning($message, array $messageParameters = [], $item = null)
    {
        $this->stepExecution->addWarning(
            $this->getName(),
            $message,
            $messageParameters,
            $item
        );

        if (!is_array($item)) {
            $item = array();
        }

        $item = $this->cleanupImageContent($item);

        $event = new InvalidItemEvent(get_class($this), $message, $messageParameters, $item);
        $this->eventDispatcher->dispatch(EventInterface::INVALID_ITEM, $event);
    }

    /**
     * Cleanup image content from item in order to avoid large
     * item line in error log.
     *
     * @param array $item
     *
     * @return array
     */
    protected function cleanupImageContent(array $item)
    {
        array_walk_recursive(
            $item,
            function (&$entry, $key) {
                if ('content' === $key) {
                    $entry = "<CUT>";
                }
            }
        );

        return $item;
    }
}
