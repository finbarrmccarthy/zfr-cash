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

namespace ZfrCash\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use ZfrCash\Entity\CustomerInterface;
use ZfrCash\Entity\Invoice;
use ZfrCash\Entity\Subscription;
use ZfrCash\Entity\VatCustomerInterface;
use ZfrCash\Event\InvoiceEvent;
use ZfrCash\Repository\CustomerRepositoryInterface;
use ZfrCash\StripePopulator\InvoicePopulatorTrait;
use ZfrStripe\Client\StripeClient;

/**
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 * @licence MIT
 */
class InvoiceService implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;
    use InvoicePopulatorTrait;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ObjectRepository
     */
    private $invoiceRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var StripeClient
     */
    private $stripeClient;

    /**
     * @param ObjectManager               $objectManager
     * @param ObjectRepository            $invoiceRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param StripeClient                $stripeClient
     */
    public function __construct(
        ObjectManager $objectManager,
        ObjectRepository $invoiceRepository,
        CustomerRepositoryInterface $customerRepository,
        StripeClient $stripeClient
    ) {
        $this->objectManager      = $objectManager;
        $this->invoiceRepository  = $invoiceRepository;
        $this->customerRepository = $customerRepository;
        $this->stripeClient       = $stripeClient;
    }

    /**
     * Force creating an invoice
     *
     * This is useful if you switch from one yearly to another yearly plan, and do not want to
     * wait for the end of the period before charging your customer
     *
     * @param  CustomerInterface $customer
     * @param  Subscription|null $subscription
     * @return Invoice
     */
    public function create(CustomerInterface $customer, Subscription $subscription = null)
    {
        $stripeInvoice = $this->stripeClient->createInvoice(array_filter([
            'customer'     => $customer->getStripeId(),
            'subscription' => $subscription ? $subscription->getStripeId() : null
        ]));

        $invoice = new Invoice();
        $this->populateInvoiceFromStripeResource($invoice, $stripeInvoice);

        $invoice->setPayer($customer);

        // If customer handles VAT, we add the VAT info to the invoice
        if ($customer instanceof VatCustomerInterface) {
            $invoice->setVatCountry($customer->getVatCountry());
            $invoice->setVatNumber($customer->getVatNumber());
        }

        $this->objectManager->persist($invoice);
        $this->objectManager->flush($invoice);
    }

    /**
     * Return a selectable object
     *
     * @return \Doctrine\Common\Collections\Selectable
     */
    public function getAll()
    {
        return $this->invoiceRepository;
    }

    /**
     * Get all the invoices for a given payer (customer)
     *
     * @param  CustomerInterface $customer
     * @return \Doctrine\Common\Collections\Selectable
     */
    public function getAllByPayer(CustomerInterface $customer)
    {
        return $this->invoiceRepository->findBy(['payer' => $customer]);
    }

    /**
     * Sync invoice from a Stripe event
     *
     * @param array $stripeEvent
     */
    public function syncFromStripeEvent(array $stripeEvent)
    {
        if (!fnmatch('invoice.*', $stripeEvent['type'])) {
            return;
        }

        $stripeInvoice = $stripeEvent['data']['object'];
        $invoice       = $this->invoiceRepository->findOneBy(['stripeId' => $stripeInvoice['id']]);

        if (null === $invoice) {
            $customer = $this->customerRepository->findOneByStripeId($stripeInvoice['customer']);

            if (null === $customer) {
                return;
            }

            $invoice = new Invoice();
            $invoice->setPayer($customer);

            // If customer handles VAT, we add the VAT info to the invoice
            if ($customer instanceof VatCustomerInterface) {
                $invoice->setVatCountry($customer->getVatCountry());
                $invoice->setVatNumber($customer->getVatNumber());
            }

            $this->objectManager->persist($invoice);
        }

        $this->populateInvoiceFromStripeResource($invoice, $stripeInvoice);

        $this->objectManager->flush($invoice);

        // If the invoice is closed, we trigger an additional event that could be used to generate, for
        // instance, a PDF and sending an email. We also want to make sure the event is not "invoice.update" nor "invoice.create"
        // because an already closed invoice can be updated with useless things like metadata, but that should not retrigger
        // such an event
        if ($invoice->isClosed() && !in_array($stripeEvent['type'], ['invoice.created', 'invoice.updated'], true)) {
            $invoiceEvent = new InvoiceEvent($invoice);
            $this->getEventManager()->trigger(InvoiceEvent::INVOICE_CLOSED, $invoiceEvent);
        }
    }
}