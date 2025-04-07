<?php

class Product
{
    private $_attributs = array();

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    public function hydrate(array $data)
    {
        foreach ($data as $champ => $valeur) {
            $this->$champ = $valeur;
        }
    }

    public function __get($champ)
    {
        if (isset($this->_attributs[$champ])) {
            return $this->_attributs[$champ];
        }
    }

    public function __set($champ, $valeur)
    {
        $this->_attributs[$champ] = $valeur;
    }
} 