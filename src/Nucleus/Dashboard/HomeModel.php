<?php

namespace Nucleus\Dashboard;

/**
 * @\Nucleus\IService\Dashboard\ModelField(name="ID", property="id", editable=false, required=true, identifier=true, link="home::get")
 * @\Nucleus\IService\Dashboard\ModelField(name="Password", property="password", type="string", listable=false)
 */
class HomeModel
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @\Nucleus\IService\Dashboard\ModelField(name="First name")
     * @\Symfony\Component\Validator\Constraints\NotBlank
     * @var string
     */
    public $firstname;

    /**
     * @\Nucleus\IService\Dashboard\ModelField(name="Last name")
     * @var string
     */
    public $lastname;

    /**
     * @\Nucleus\IService\Dashboard\ModelField(name="Age", type="int[]")
     */
    public $age;

    /**
     * @\Nucleus\IService\Dashboard\ModelLoader
     * @param int $id
     */
    public static function find($id)
    {
        return new self($id, $id);
    }

    public function __construct($id = null, $firstname = null, $lastname = null, $password = null)
    {
        $this->id = $id;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->password = $password;
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(title="Delete", icon="trash")
     */
    public function delete()
    {
        
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}
