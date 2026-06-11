const months = 'ABCDEHLMPRST';
const even = Object.fromEntries('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('').map((char, index) => [char, index < 10 ? index : index - 10]));
const oddValues = [1, 0, 5, 7, 9, 13, 15, 17, 19, 21, 2, 4, 18, 20, 11, 3, 6, 8, 12, 14, 16, 10, 22, 25, 24, 23];
const odd = Object.fromEntries('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('').map((char, index) => [char, index < 10 ? oddValues[index] : oddValues[index - 10]]));

const consonants = (value) => value.toUpperCase().replace(/[^A-Z]/g, '').replace(/[AEIOU]/g, '');
const vowels = (value) => value.toUpperCase().replace(/[^AEIOU]/g, '');
const surnameCode = (value) => `${consonants(value)}${vowels(value)}XXX`.slice(0, 3);
const nameCode = (value) => {
    const letters = consonants(value);

    return letters.length >= 4 ? `${letters[0]}${letters[2]}${letters[3]}` : `${letters}${vowels(value)}XXX`.slice(0, 3);
};

const calculateFiscalCode = ({ firstName, lastName, birthDate, gender, birthPlace, findCity }) => {
    if (!firstName || !lastName || !birthDate || !birthPlace) return '';

    const city = findCity(birthPlace);
    if (!city) return '';

    const date = new Date(`${birthDate}T00:00:00`);
    if (Number.isNaN(date.getTime())) return '';

    let day = date.getDate();
    if (gender === 'F') day += 40;

    const partial = `${surnameCode(lastName)}${nameCode(firstName)}${String(date.getFullYear()).slice(-2)}${months[date.getMonth()]}${String(day).padStart(2, '0')}${city.cadastralCode}`;
    const checksum = partial.split('').reduce((sum, char, index) => sum + (index % 2 === 0 ? odd[char] : even[char]), 0) % 26;

    return `${partial}${String.fromCharCode(65 + checksum)}`;
};

document.addEventListener('DOMContentLoaded', async () => {
    const form = document.querySelector('[data-patient-form]');
    if (!form) return;

    const { findCity, italianCities } = await import('./italian-cities');

    const field = (name) => form.querySelector(`[name="${name}"]`);
    const cityList = document.getElementById('italian-cities');

    if (cityList && cityList.children.length === 0) {
        italianCities.forEach((city) => {
            const option = document.createElement('option');
            option.value = city.name;
            option.label = `${city.name} (${city.province})`;
            cityList.appendChild(option);
        });
    }

    const fallback = {
        'preview-first-name': 'Nome',
        'preview-last-name': 'Cognome',
        'preview-profession': 'Professione non inserita',
        'preview-phone': 'Non inserito',
        'preview-email': 'Non inserita',
        'preview-fiscal-code': 'Non inserito',
    };

    const updateInitials = () => {
        const first = document.getElementById('preview-first-name')?.textContent?.trim() || '';
        const last = document.getElementById('preview-last-name')?.textContent?.trim() || '';
        const initials = `${last[0] || ''}${first[0] || ''}`.toUpperCase();
        document.getElementById('preview-initials').textContent = initials || '--';
    };

    const syncPreviewField = (input) => {
        const target = document.getElementById(input.dataset.previewTarget);
        if (!target) return;

        target.textContent = input.value.trim() || fallback[input.dataset.previewTarget] || 'Non inserito';
        updateInitials();
    };

    form.querySelectorAll('.patient-preview-field').forEach((input) => {
        input.addEventListener('input', () => syncPreviewField(input));
        syncPreviewField(input);
    });

    const syncAge = () => {
        const birthDate = field('birth_date');
        const ageTarget = document.getElementById('computed-age');
        if (!birthDate || !ageTarget || !birthDate.value) {
            if (ageTarget) ageTarget.textContent = 'n.d.';
            return;
        }

        const born = new Date(`${birthDate.value}T00:00:00`);
        const today = new Date();
        let age = today.getFullYear() - born.getFullYear();
        const beforeBirthday = today.getMonth() < born.getMonth() || (today.getMonth() === born.getMonth() && today.getDate() < born.getDate());
        if (beforeBirthday) age--;
        ageTarget.textContent = Number.isFinite(age) && age >= 0 ? `${age} anni` : 'n.d.';
    };

    const syncResidence = () => {
        const city = findCity(field('city')?.value || '');
        if (!city) return;

        const province = field('province');
        const postalCode = field('postal_code');
        if (province) province.value = city.province;
        if (postalCode) postalCode.value = city.zip || city.caps?.[0] || '';
    };

    const syncFiscalCode = () => {
        const fiscalCode = field('fiscal_code');
        if (!fiscalCode) return;

        const code = calculateFiscalCode({
            lastName: field('last_name')?.value || '',
            firstName: field('first_name')?.value || '',
            birthDate: field('birth_date')?.value || '',
            gender: field('gender')?.value || '',
            birthPlace: field('birth_place')?.value || '',
            findCity,
        });

        if (!code) return;

        fiscalCode.value = code;
        fiscalCode.dispatchEvent(new Event('input'));
    };

    ['last_name', 'first_name', 'birth_date', 'gender', 'birth_place'].forEach((name) => {
        field(name)?.addEventListener('input', syncFiscalCode);
        field(name)?.addEventListener('change', syncFiscalCode);
    });

    field('birth_date')?.addEventListener('change', syncAge);
    field('birth_date')?.addEventListener('input', syncAge);
    field('city')?.addEventListener('change', syncResidence);
    field('city')?.addEventListener('blur', syncResidence);

    document.getElementById('recalculate-fiscal-code')?.addEventListener('click', syncFiscalCode);
    document.getElementById('normalize-fiscal-code')?.addEventListener('click', () => {
        const fiscalCode = field('fiscal_code');
        if (!fiscalCode) return;

        fiscalCode.value = fiscalCode.value.trim().toUpperCase();
        fiscalCode.dispatchEvent(new Event('input'));
    });

    syncAge();
    syncFiscalCode();
    syncResidence();
});
