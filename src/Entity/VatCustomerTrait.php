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

namespace ZfrCash\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait for a Stripe customer
 *
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 * @licence MIT
 *
 * @ORM\MappedSuperclass
 */
trait VatCustomerTrait
{
    use CustomerTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=60)
     */
    protected $vatNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=2)
     */
    protected $vatCountry;

    /**
     * {@inheritDoc}
     */
    public function setVatNumber($vatNumber = null)
    {
        $this->vatNumber = $vatNumber;
    }

    /**
     * {@inheritDoc}
     */
    public function getVatNumber()
    {
        return $this->vatNumber;
    }

    /**
     * {@inheritDoc}
     */
    public function setVatCountry($vatCountry = null)
    {
        $this->vatCountry = $vatCountry;
    }

    /**
     * {@inheritDoc}
     */
    public function getVatCountry()
    {
        return $this->vatCountry;
    }
}
