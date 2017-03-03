<?php

namespace JsonApiBundle\JsonApiResource;

class HasManyRelationship extends Relationship
{
    /**
     * Add Method
     * @var string
     */
    private $addMethod;

    /**
     * Remove Method
     * @var string
     */
    private $removeMethod;

    /**
     * @{inheritDoc}
     */
    public function addToJson($entity, array $json)
    {
        if (is_array($entity)) {
            if (array_key_exists($this->getEntity(), $entity)) {
                $objects = $entity[$this->getEntity()]->{'get' . ucfirst($this->getProperty())}();
            }
        } else {
            $objects = $entity->{'get' . ucfirst($this->getProperty())}();
        }

        $relationJson = ['data' => []];
        foreach($objects as $object) {
            $relationJson['data'][] = [
                'type' => $this->getResource(),
                'id' => $this->getIdForRelatedEntity($object)
            ];
        }


        $json['relationships'][$this->getJsonName()] = $relationJson;
        return $json;
    }

    /**
     * @{inheritDoc}
     */
    public function addToEntity($entity, array $relationData, ResourceManager $manager)
    {
        if (is_array($entity)) {
            if (array_key_exists($this->getEntity(), $entity)) {
                $alteredEntity = $entity[$this->getEntity()];
            } else {
                throw new \Exception('Cannot match requested entity with entity in map');
            }
        } else {
            $alteredEntity = $entity;
        }

        // Check that the data key is present
        if (array_key_exists('data', $relationData)) {
            // Since this is a full replacement, get the existing first
            $original = $alteredEntity->{'get' . ucfirst($this->getProperty())}();
            foreach($original as $item) {
                $alteredEntity->{$this->removeMethod}($item);
            }

            // Check if individual reference object or array or reference objects
            $identifiers = [];
            if (0 < count($relationData['data'])) {
                if (is_array(reset($relationData['data']))) {
                    foreach($relationData['data'] as $data) {
                        if (isset($data['type']) && isset($data['id'])) {
                            $identifiers[] = new ResourceIdentifier($data['type'], $data['id']);
                        } else {
                            throw new \Exception('Identifier must have a type and an id');
                        }
                    }
                } else {
                    if (isset($relationData['data']['type']) && isset($relationData['data']['id'])) {
                        $identifiers[] = new ResourceIdentifier($relationData['data']['type'], $relationData['data']['id']);
                    } else {
                        throw new \Exception('Identifier must have a type and an id');
                    }
                }
            }

            foreach($identifiers as $identifier) {
                $loadedEntity = $manager->loadEntityFromIdentifier($identifier);
                if ($loadedEntity) {
                    $alteredEntity->{$this->addMethod}($loadedEntity);
                }
            }
        }

        return $entity;
    }

    /**
     * Get the value of Add Method
     * @return string
     */
    public function getAddMethod()
    {
        return $this->addMethod;
    }

    /**
     * Set the value of Add Method
     * @param string addMethod
     * @return self
     */
    public function setAddMethod($addMethod)
    {
        $this->addMethod = $addMethod;
        return $this;
    }

    /**
     * Get the value of Remove Method
     * @return string
     */
    public function getRemoveMethod()
    {
        return $this->removeMethod;
    }

    /**
     * Set the value of Remove Method
     * @param string removeMethod
     * @return self
     */
    public function setRemoveMethod($removeMethod)
    {
        $this->removeMethod = $removeMethod;
        return $this;
    }

    /**
     * Get the value of Set Method
     * @return string
     */
    public function getSetMethod()
    {
        return $this->setMethod;
    }

    /**
     * Set the value of Set Method
     * @param string setMethod
     * @return self
     */
    public function setSetMethod($setMethod)
    {
        $this->setMethod = $setMethod;
        return $this;
    }

}
