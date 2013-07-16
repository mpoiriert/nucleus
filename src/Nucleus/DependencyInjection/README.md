DependencyInjection
===================

The system is using a dependency injection patern and a container.


##Modifying the Container Base on Annotation

If you need to create a custom annotation that modify the container you need to:

 * Create a annotation class base on [Doctrine Annotation](http://docs.doctrine-project.org/projects/doctrine-common/en/latest/reference/annotations.html)
 * Create a class that implements Nucleus\DependencyInjection\IAnnotationContainerGenerator
 * Register this class in a nucleus.json configuration file under nucleus->annotationContainerGenerator

At the build time of the container the class who implements Nucleus\DependencyInjection\IAnnotationContainerGenerator
will receive the container generation context. From there you can modify
the container.

Here is a exemple for the tag annotation.

This is the Tag annotation class

    //Annotation

    namespace Nucleus\IService\DependencyInjection;

    /**
     * @Annotation
     * @Target({"CLASS"})
     */
    class Tag
    {
        /**
         * @var string
         */
        public $name;
    }

This is the class that will modify the container to tag services.

    //ContainerGenerator

    namespace Nucleus\DependencyInjection;

    class TagAnnotationContainerGenerator implements IServiceContainerGeneratorAnnotation
    {
        /**
         * @param GenerationContext $context
         */
        public function processContainerBuilder(GenerationContext $context)
        {
            $annotation = $context->getAnnotation();
            /* @var $annotation \Nucleus\IService\DependencyInjection\Tag */
            $context->getServiceDefinition()->addTag($annotation->name);
        }
    }

** Don't forget to put your use if you are not working in the Nucleus\DependencyInjection namespace **

Now that this is done you can associate those 2 together in a nucleus.json configuration file

    {
        "nucleus": {
            "annotationContainerGenerator": {
                "Nucleus\\IService\\DependencyInjection\\Tag": {
                    "class": "Nucleus\\DependencyInjection\\TagAnnotationContainerGenerator"
                }
            }
        }
    }

And that's it ! You can now use the @\Nucleus\IService\DependencyInjection\Tag 
annotation.

** You should check the API of the Nucleus\DependencyInjection\GenerationContext
and Symfony\Component\DependencyInjection\ContainerBuilder to know what you can
do base on the generation**




