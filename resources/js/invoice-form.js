document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-invoice-service-input]').forEach((input) => {
        const form = input.closest('form');
        const unitAmount = form?.querySelector('[data-invoice-unit-amount]');
        const quantity = form?.querySelector('[data-invoice-quantity]');
        const issuedAt = form?.querySelector('input[name="issued_at"]');
        const paymentDate = form?.querySelector('[data-invoice-payment-date]');
        const amount = form?.querySelector('[data-invoice-total-amount]');
        const lineTotalDisplay = form?.querySelector('[data-invoice-line-total-display]');
        const totalDisplay = form?.querySelector('[data-invoice-total-display]');
        const socialSecurityDisplay = form?.querySelector('[data-invoice-social-security-display]');
        const vatDisplay = form?.querySelector('[data-invoice-vat-display]');
        const stampDisplay = form?.querySelector('[data-invoice-stamp-display]');
        const description = form?.querySelector('[data-invoice-description]');
        const services = JSON.parse(input.dataset.services || '[]');
        const settings = JSON.parse(input.dataset.settings || '{}');
        const invoiceId = input.dataset.invoiceId || '';
        let activeService = services.find((item) => item.name === input.value) || services[0] || {};
        const number = (value) => Number(value).toFixed(2);
        const money = (value) => new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR',
        }).format(value);

        const updateTotal = () => {
            const unit = Number.parseFloat(unitAmount?.value || '0') || 0;
            const qty = Number.parseFloat(quantity?.value || '1') || 1;
            const lineTotal = unit * qty;
            const socialSecurityRate = Number.parseFloat(activeService.social_security_rate ?? settings.invoice_social_security_rate ?? '0') || 0;
            const vatRate = Number.parseFloat(activeService.vat_rate ?? '0') || 0;
            const taxable = lineTotal + (lineTotal * socialSecurityRate / 100);
            const vat = taxable * vatRate / 100;
            const stampThreshold = Number.parseFloat(settings.invoice_stamp_threshold ?? '77.47') || 0;
            const stampAmount = Number.parseFloat(settings.invoice_stamp_amount ?? '2.00') || 0;
            const stamp = activeService.stamp_duty && taxable > stampThreshold ? stampAmount : 0;
            const total = taxable + vat + stamp;

            if (amount) {
                amount.value = total.toFixed(2);
            }

            const lineAmount = form?.querySelector('[data-invoice-line-amount]');
            if (lineAmount) {
                lineAmount.value = lineTotal.toFixed(2);
            }

            if (lineTotalDisplay) {
                lineTotalDisplay.textContent = money(lineTotal);
            }

            if (socialSecurityDisplay) {
                socialSecurityDisplay.textContent = `${socialSecurityRate.toLocaleString('it-IT', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                })}% - ${money(lineTotal * socialSecurityRate / 100)}`;
            }

            if (vatDisplay) {
                vatDisplay.textContent = `${vatRate.toLocaleString('it-IT', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                })}% ${activeService.vat_nature || settings.invoice_vat_nature || ''} - ${money(vat)}`;
            }

            if (stampDisplay) {
                stampDisplay.textContent = money(stamp);
            }

            if (totalDisplay) {
                totalDisplay.textContent = money(total);
            }

            if (description) {
                description.value = `IDFattura: ${invoiceId} | Importo: ${number(lineTotal)} | Inps: ${number(lineTotal * socialSecurityRate / 100)} | Bollo: ${number(stamp)}`;
            }
        };

        input.addEventListener('change', () => {
            const service = services.find((item) => item.name === input.value);
            if (!service) {
                return;
            }

            activeService = service;

            if (unitAmount && service.amount !== undefined) {
                unitAmount.value = service.amount;
            }

            updateTotal();
        });

        unitAmount?.addEventListener('input', updateTotal);
        quantity?.addEventListener('input', updateTotal);
        issuedAt?.addEventListener('change', () => {
            if (paymentDate && !paymentDate.dataset.manuallyChanged) {
                paymentDate.value = issuedAt.value;
            }
        });
        paymentDate?.addEventListener('input', () => {
            paymentDate.dataset.manuallyChanged = '1';
        });
        updateTotal();
    });
});
