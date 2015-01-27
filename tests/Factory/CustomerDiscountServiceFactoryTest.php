<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ZfrCashTest\Factory;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use PHPUnit_Framework_TestCase;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfrCash\Entity\CustomerDiscount;
use ZfrCash\Entity\CustomerInterface;
use ZfrCash\Factory\CustomerDiscountServiceFactory;
use ZfrCash\Options\ModuleOptions;
use ZfrCash\Repository\CustomerRepositoryInterface;
use ZfrCash\Service\CustomerDiscountService;
use ZfrStripe\Client\StripeClient;

class CustomerDiscountServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $moduleOptions              = new ModuleOptions(['object_manager' => 'my_object_manager']);
        $objectManager              = $this->getMock(ObjectManager::class);
        $stripeClient               = $this->getMock(StripeClient::class, [], [], '', false);
        $customerDiscountRepository = $this->getMock(ObjectRepository::class);
        $customerRepository         = $this->getMock(CustomerRepositoryInterface::class);

        $objectManager->expects($this->at(0))->method('getRepository')->with(CustomerDiscount::class)->willReturn($customerDiscountRepository);
        $objectManager->expects($this->at(1))->method('getRepository')->with(CustomerInterface::class)->willReturn($customerRepository);

        $serviceLocator = $this->getMock(ServiceLocatorInterface::class);

        $serviceLocator->expects($this->at(0))->method('get')->with(ModuleOptions::class)->willReturn($moduleOptions);
        $serviceLocator->expects($this->at(1))->method('get')->with('my_object_manager')->willReturn($objectManager);
        $serviceLocator->expects($this->at(2))->method('get')->with(StripeClient::class)->willReturn($stripeClient);

        $factory  = new CustomerDiscountServiceFactory();
        $instance = $factory->createService($serviceLocator);

        $this->assertInstanceOf(CustomerDiscountService::class, $instance);
    }
}