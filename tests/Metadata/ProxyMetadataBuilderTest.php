<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\MediaBundle\Tests\Metadata;

use AmazonS3 as AmazonClient;
use Gaufrette\Adapter\AmazonS3;
use PHPUnit\Framework\TestCase;
use Sonata\MediaBundle\Filesystem\Local;
use Sonata\MediaBundle\Filesystem\Replicate;
use Sonata\MediaBundle\Metadata\ProxyMetadataBuilder;

class ProxyMetadataBuilderTest extends TestCase
{
    public function testProxyAmazon()
    {
        $amazon = $this->getMockBuilder('Sonata\MediaBundle\Metadata\AmazonMetadataBuilder')->disableOriginalConstructor()->getMock();
        $amazon->expects($this->once())
            ->method('get')
            ->will($this->returnValue(['key' => 'amazon']));

        $noop = $this->createMock('Sonata\MediaBundle\Metadata\NoopMetadataBuilder');
        $noop->expects($this->never())
            ->method('get')
            ->will($this->returnValue(['key' => 'noop']));

        //adapter cannot be mocked
        $amazonclient = new AmazonClient(['key' => 'XXXXXXXXXXXX', 'secret' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX']);
        $adapter = new AmazonS3($amazonclient, '');

        $filesystem = $this->getMockBuilder('Gaufrette\Filesystem')->disableOriginalConstructor()->getMock();
        $filesystem->expects($this->any())->method('getAdapter')->will($this->returnValue($adapter));

        $provider = $this->createMock('Sonata\MediaBundle\Provider\MediaProviderInterface');
        $provider->expects($this->any())->method('getFilesystem')->will($this->returnValue($filesystem));

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->any())
            ->method('getProviderName')
            ->will($this->returnValue('sonata.media.provider.image'));

        $filename = '/test/folder/testfile.png';

        $container = $this->getContainerMock([
            'sonata.media.metadata.noop' => $noop,
            'sonata.media.metadata.amazon' => $amazon,
            'sonata.media.provider.image' => $provider,
        ]);

        $proxymetadatabuilder = new ProxyMetadataBuilder($container);

        $this->assertSame(['key' => 'amazon'], $proxymetadatabuilder->get($media, $filename));
    }

    public function testProxyLocal()
    {
        $amazon = $this->getMockBuilder('Sonata\MediaBundle\Metadata\AmazonMetadataBuilder')->disableOriginalConstructor()->getMock();
        $amazon->expects($this->never())
            ->method('get')
            ->will($this->returnValue(['key' => 'amazon']));

        $noop = $this->createMock('Sonata\MediaBundle\Metadata\NoopMetadataBuilder');
        $noop->expects($this->once())
            ->method('get')
            ->will($this->returnValue(['key' => 'noop']));

        //adapter cannot be mocked
        $adapter = new Local('');

        $filesystem = $this->getMockBuilder('Gaufrette\Filesystem')->disableOriginalConstructor()->getMock();
        $filesystem->expects($this->any())->method('getAdapter')->will($this->returnValue($adapter));

        $provider = $this->createMock('Sonata\MediaBundle\Provider\MediaProviderInterface');
        $provider->expects($this->any())->method('getFilesystem')->will($this->returnValue($filesystem));

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->any())
            ->method('getProviderName')
            ->will($this->returnValue('sonata.media.provider.image'));

        $filename = '/test/folder/testfile.png';

        $container = $this->getContainerMock([
            'sonata.media.metadata.noop' => $noop,
            'sonata.media.metadata.amazon' => $amazon,
            'sonata.media.provider.image' => $provider,
        ]);

        $proxymetadatabuilder = new ProxyMetadataBuilder($container);

        $this->assertSame(['key' => 'noop'], $proxymetadatabuilder->get($media, $filename));
    }

    public function testProxyNoProvider()
    {
        $amazon = $this->getMockBuilder('Sonata\MediaBundle\Metadata\AmazonMetadataBuilder')->disableOriginalConstructor()->getMock();
        $amazon->expects($this->never())
            ->method('get')
            ->will($this->returnValue(['key' => 'amazon']));

        $noop = $this->createMock('Sonata\MediaBundle\Metadata\NoopMetadataBuilder');
        $noop->expects($this->never())
            ->method('get')
            ->will($this->returnValue(['key' => 'noop']));

        //adapter cannot be mocked
        $adapter = new Local('');

        $filesystem = $this->getMockBuilder('Gaufrette\Filesystem')->disableOriginalConstructor()->getMock();
        $filesystem->expects($this->any())->method('getAdapter')->will($this->returnValue($adapter));

        $provider = $this->createMock('Sonata\MediaBundle\Provider\MediaProviderInterface');
        $provider->expects($this->any())->method('getFilesystem')->will($this->returnValue($filesystem));

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->any())
            ->method('getProviderName')
            ->will($this->returnValue('wrongprovider'));

        $filename = '/test/folder/testfile.png';

        $container = $this->getContainerMock([
            'sonata.media.metadata.noop' => $noop,
            'sonata.media.metadata.amazon' => $amazon,
            'sonata.media.provider.image' => $provider,
        ]);

        $proxymetadatabuilder = new ProxyMetadataBuilder($container);

        $this->assertSame([], $proxymetadatabuilder->get($media, $filename));
    }

    public function testProxyReplicateWithAmazon()
    {
        $amazon = $this->getMockBuilder('Sonata\MediaBundle\Metadata\AmazonMetadataBuilder')->disableOriginalConstructor()->getMock();
        $amazon->expects($this->once())
            ->method('get')
            ->will($this->returnValue(['key' => 'amazon']));

        $noop = $this->createMock('Sonata\MediaBundle\Metadata\NoopMetadataBuilder');
        $noop->expects($this->never())
            ->method('get')
            ->will($this->returnValue(['key' => 'noop']));

        //adapter cannot be mocked
        $amazonclient = new AmazonClient(['key' => 'XXXXXXXXXXXX', 'secret' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX']);
        $adapter1 = new AmazonS3($amazonclient, '');
        $adapter2 = new Local('');
        $adapter = new Replicate($adapter1, $adapter2);

        $filesystem = $this->getMockBuilder('Gaufrette\Filesystem')->disableOriginalConstructor()->getMock();
        $filesystem->expects($this->any())->method('getAdapter')->will($this->returnValue($adapter));

        $provider = $this->createMock('Sonata\MediaBundle\Provider\MediaProviderInterface');
        $provider->expects($this->any())->method('getFilesystem')->will($this->returnValue($filesystem));

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->any())
            ->method('getProviderName')
            ->will($this->returnValue('sonata.media.provider.image'));

        $filename = '/test/folder/testfile.png';

        $container = $this->getContainerMock([
            'sonata.media.metadata.noop' => $noop,
            'sonata.media.metadata.amazon' => $amazon,
            'sonata.media.provider.image' => $provider,
        ]);

        $proxymetadatabuilder = new ProxyMetadataBuilder($container);

        $this->assertSame(['key' => 'amazon'], $proxymetadatabuilder->get($media, $filename));
    }

    public function testProxyReplicateWithoutAmazon()
    {
        $amazon = $this->getMockBuilder('Sonata\MediaBundle\Metadata\AmazonMetadataBuilder')->disableOriginalConstructor()->getMock();
        $amazon->expects($this->never())
            ->method('get')
            ->will($this->returnValue(['key' => 'amazon']));

        $noop = $this->createMock('Sonata\MediaBundle\Metadata\NoopMetadataBuilder');
        $noop->expects($this->once())
            ->method('get')
            ->will($this->returnValue(['key' => 'noop']));

        //adapter cannot be mocked
        $adapter1 = new Local('');
        $adapter2 = new Local('');
        $adapter = new Replicate($adapter1, $adapter2);

        $filesystem = $this->getMockBuilder('Gaufrette\Filesystem')->disableOriginalConstructor()->getMock();
        $filesystem->expects($this->any())->method('getAdapter')->will($this->returnValue($adapter));

        $provider = $this->createMock('Sonata\MediaBundle\Provider\MediaProviderInterface');
        $provider->expects($this->any())->method('getFilesystem')->will($this->returnValue($filesystem));

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->any())
            ->method('getProviderName')
            ->will($this->returnValue('sonata.media.provider.image'));

        $filename = '/test/folder/testfile.png';

        $container = $this->getContainerMock([
            'sonata.media.metadata.noop' => $noop,
            'sonata.media.metadata.amazon' => $amazon,
            'sonata.media.provider.image' => $provider,
        ]);

        $proxymetadatabuilder = new ProxyMetadataBuilder($container);

        $this->assertSame(['key' => 'noop'], $proxymetadatabuilder->get($media, $filename));
    }

    /**
     * Return a mock object for the DI ContainerInterface.
     *
     * @param array $services A key-value list of services the container contains
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContainerMock(array $services)
    {
        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container
            ->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($service) use ($services) {
                return $services[$service];
            }))
        ;
        $container
            ->expects($this->any())
            ->method('has')
            ->will($this->returnCallback(function ($service) use ($services) {
                if (isset($services[$service])) {
                    return true;
                }

                return false;
            }))
        ;

        return $container;
    }
}
