<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Entity;

/**
 * Simple mapping.
 *
 */
class SimpleMapping
{
    /** @var integer */
    protected $id;

    /** @var string */
    protected $identifier;

    /** @var string */
    protected $source;

    /** @var string */
    protected $target;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $identifier
     *
     * @return SimpleMapping
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $source
     *
     * @return SimpleMapping
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $target
     *
     * @return SimpleMapping
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }
}
