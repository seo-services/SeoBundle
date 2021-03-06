<?php

namespace Symfony\Cmf\SeoBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Cmf\Bundle\SeoBundle\DependencyInjection\CmfSeoExtension;
use Symfony\Component\DependencyInjection\Definition;

class CmfSeoExtensionTest extends AbstractExtensionTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getContainerExtensions()
    {
        return array(
            new CmfSeoExtension(),
        );
    }

    public function testDefaults()
    {
        $this->load(array(
            'title'         => 'Default title',
            'description'   => 'Default description.',
        ));

        $this->assertContainerBuilderHasParameter('cmf_seo.title', 'Default title');
        $this->assertContainerBuilderHasParameter('cmf_seo.description', 'Default description.');
        $this->assertContainerBuilderHasParameter('cmf_seo.translation_domain', 'messages');
        $this->assertContainerBuilderHasParameter('cmf_seo.original_route_pattern', 'canonical');
        $this->assertContainerBuilderHasParameter('cmf_seo.content_key', 'contentDocument');
        $this->assertContainerBuilderHasService('cmf_seo.error.suggestion_provider.controller');
        $this->assertContainerBuilderNotHasService('cmf_seo.error.suggestion_provider.parent');
        $this->assertContainerBuilderNotHasService('cmf_seo.error.suggestion_provider.sibling');
    }

    public function testPersistencePHPCR()
    {
        $this->container->setParameter(
            'kernel.bundles',
            array(
                'CmfRoutingBundle' => true,
                'SonataDoctrinePHPCRAdminBundle' => true,
                'DoctrinePHPCRBundle' => true,
                'BurgovKeyValueFormBundle' => true,
            )
        );
        $this->load(array(
            'title'         => 'Default title',
            'description'   => 'Default description.',
            'persistence'   => array(
                'phpcr' => true,
            ),
            'sitemap' => true,
        ));

        $this->assertContainerBuilderHasService(
            'cmf_seo.sitemap.phpcr_provider',
            'Symfony\Cmf\Bundle\SeoBundle\Doctrine\Phpcr\SitemapUrlInformationProvider'
        );
    }

    public function testPersistenceORM()
    {
        $this->container->setParameter(
            'kernel.bundles',
            array(
                'CmfRoutingBundle' => true,
                'SonataDoctrineORMBundle' => true,
                'BurgovKeyValueFormBundle' => true,
            )
        );

        $this->load(array(
            'title'         => 'Default title',
            'description'   => 'Default description.',
            'persistence'   => array(
                'orm'   => true,
            ),
        ));
    }

    public function testAdminExtension()
    {
        $this->container->setParameter(
            'kernel.bundles',
            array(
                'CmfRoutingBundle' => true,
                'SonataDoctrineORMBundle' => true,
                'DoctrinePHPCRBundle' => true,
            )
        );

        $this->load(array(
            'title'                  => 'Default title',
            'description'            => 'Default description.',
            'sonata_admin_extension' => true,
            'persistence'            => array(
                'phpcr' => true,
            ),
        ));

        $this->assertContainerBuilderHasService('cmf_seo.admin_extension', 'Symfony\Cmf\Bundle\SeoBundle\Admin\Extension\SeoContentAdminExtension');
    }

    public function testAlternateLocaleWithPhpcr()
    {
        $this->container->setParameter(
            'kernel.bundles',
            array(
                'DoctrinePHPCRBundle' => true,
                'CmfRoutingBundle' => true,
            )
        );
        $this->load(array(
            'persistence'   => array(
                'phpcr' => true,
            ),
            'alternate_locale' => array(
                'enabled' => true
            ),
        ));

        $this->assertContainerBuilderHasService(
            'cmf_seo.alternate_locale.provider_phpcr',
            'Symfony\Cmf\Bundle\SeoBundle\Doctrine\Phpcr\AlternateLocaleProvider'
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'cmf_seo.event_listener.seo_content',
            'setAlternateLocaleProvider',
            array($this->container->getDefinition('cmf_seo.alternate_locale.provider_phpcr'))
        );
    }

    public function testAlternateLocaleWithCustomProvider()
    {
        $this->container->setParameter(
            'kernel.bundles',
            array()
        );
        $this->container->setDefinition('some_alternate_locale_provider', new Definition());
        $this->load(array(
            'persistence' => array(
                'phpcr' => true,
            ),
            'alternate_locale' => array(
                'provider_id' => 'some_alternate_locale_provider'
            ),
            'sitemap' => true,
        ));

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'cmf_seo.event_listener.seo_content',
            'setAlternateLocaleProvider',
            array($this->container->getDefinition('some_alternate_locale_provider'))
        );
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'cmf_seo.sitemap.phpcr_provider',
            'setAlternateLocaleProvider',
            array($this->container->getDefinition('some_alternate_locale_provider'))
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testExceptionForNonExistingBurgovBundleWithAdminExtension()
    {
        $this->container->setParameter(
            'kernel.bundles',
            array(
                'CmfRoutingBundle' => true,
                'SonataDoctrinePHPCRAdminBundle' => true,
                'DoctrinePHPCRBundle' => true,
            )
        );
        $this->load(array(
            'persistence'   => array(
                'phpcr' => true,
            )
        ));
    }

    public function testErrorHandlingPHPCR()
    {
        $this->container->setParameter(
            'kernel.bundles',
            array(
                'CmfRoutingBundle' => true,
            )
        );
        $this->load(array(
            'persistence'   => array(
                'phpcr' => true,
            ),
            'error' => array(
                'enable_parent_provider' => true,
                'enable_sibling_provider' => true,
            )
        ));

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'cmf_seo.error.suggestion_provider.sibling',
            'cmf_seo.suggestion_provider',
            array('group' => 'sibling')
        );
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'cmf_seo.error.suggestion_provider.parent',
            'cmf_seo.suggestion_provider',
            array('group' => 'parent')
        );
    }

    public function testSitemapConfiguration()
    {
        $this->container->setParameter(
            'kernel.bundles',
            array()
        );
        $this->load(array(
            'sitemap'   => array(
                'default_change_frequency' => 'never',
            ),
            'persistence'   => array(
                'phpcr' => true,
            ),
        ));

        $this->assertContainerBuilderHasService(
            'cmf_seo.sitemap.controller',
            'Symfony\Cmf\Bundle\SeoBundle\Controller\SitemapController'
        );
        $this->assertContainerBuilderHasService(
            'cmf_seo.sitemap.url_information_provider',
            'Symfony\Cmf\Bundle\SeoBundle\Sitemap\ChainProvider'
        );
        $this->assertContainerBuilderHasService(
            'cmf_seo.sitemap.phpcr_provider',
            'Symfony\Cmf\Bundle\SeoBundle\Doctrine\Phpcr\SitemapUrlInformationProvider'
        );
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'cmf_seo.sitemap.phpcr_provider',
            'cmf_seo.sitemap.url_information_provider'
        );
    }

    public function testDisableSeoContentListener()
    {
        $this->container->setParameter(
            'kernel.bundles',
            array(
                'DoctrinePHPCRBundle' => true,
                'CmfRoutingBundle' => true,
            )
        );
        $this->load(array(
            'persistence'   => array(
                'phpcr' => true,
            ),
            'content_listener' => array(
                'enabled' => false,
            ),
        ));

        $this->assertContainerBuilderNotHasService(
            'cmf_seo.event_listener.seo_content'
        );
    }
}
