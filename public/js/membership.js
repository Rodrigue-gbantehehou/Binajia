// membership.js

// ----------------------------
// Variables globales
// ----------------------------
let currentStep = 1;
let selectedPlan = 'standard';
let formData = {};
let photoData = null;
let lastTransactionId = null;
let lastTransactionStatus = null;
let callbackFired = false;
let fedapayReady = false;

// Codes de numérotation par pays
const countryDial = {
    'BJ': '+229','NG': '+234','FR': '+33','US': '+1','GB': '+44','CI': '+225',
    'TG': '+228','SN': '+221','CM': '+237','GH': '+233','ZA': '+27','DE': '+49',
    'ES': '+34','IT': '+39','CA': '+1'
};

// Montants par plan
const planAmountXOF = { basic: 5000, standard: 5000, premium: 5000 };

// ----------------------------
// Helpers
// ----------------------------
function val(id) { return document.getElementById(id)?.value || ''; }
function byId(id) { return document.getElementById(id); }

function amountForSelectedPlan() {
    return planAmountXOF[selectedPlan] || 5000;
}

function getDial() {
    return byId('dialPreview')?.textContent.trim() || '+229';
}

function normalizeLocalPhone(num) {
    return (num || '').replace(/\s+/g, '').replace(/[^0-9]/g, '').replace(/^0+/, '');
}

// ----------------------------
// Mise à jour du code pays
// ----------------------------
function updateDialFromCountry() {
    const sel = byId('country');
    const code = (sel?.value || '').toUpperCase();
    const dial = countryDial[code] || '+229';
    if(byId('dialPreview')) byId('dialPreview').textContent = dial;

    const phoneEl = byId('phone');
    if(phoneEl && phoneEl.value.trim() !== '' && !/^\+\d{1,3}/.test(phoneEl.value.trim())) {
        phoneEl.value = phoneEl.value.replace(/^0+/, '');
    }
}

document.addEventListener('change', e => {
    if(e.target?.id === 'country') updateDialFromCountry();
});

document.addEventListener('DOMContentLoaded', updateDialFromCountry);

// ----------------------------
// Upload de photo
// ----------------------------
function handlePhotoUpload(ev) {
    const file = ev.target.files[0];
    if(!file) return;

    if(file.size > 5*1024*1024){
        alert('La photo ne doit pas dépasser 5MB');
        return;
    }

    const reader = new FileReader();
    reader.onload = e => {
        photoData = e.target.result;
        byId('photoPreview').innerHTML = `<img src="${photoData}" class="w-full h-full object-cover">`;
    };
    reader.readAsDataURL(file);
}

// ----------------------------
// Validation du formulaire
// ----------------------------
async function checkEmailExists(email) {
    try {
        const res = await fetch('/check-email', { // Remplacer par path('app_check_email')
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ email })
        });
        return await res.json();
    } catch(e) {
        console.error('Erreur email:', e);
        return { ok: false, message: 'Erreur serveur' };
    }
}

async function validateAndContinue() {
    const email = val('email');
    if(!/^\S+@\S+\.\S+$/.test(email)){
        alert('Veuillez entrer une adresse email valide.');
        return false;
    }

    const check = await checkEmailExists(email);
    if(!check.ok){
        alert(check.message || 'Cet email est déjà utilisé.');
        return false;
    }

    const fullPhone = `${getDial()}${normalizeLocalPhone(val('phone'))}`;

    formData = {
        firstName: val('firstName'),
        lastName: val('lastName'),
        email: email,
        phone: fullPhone,
        country: val('country'),
        birthDate: val('birthDate'),
        terms: byId('terms')?.checked
    };

    if(!formData.firstName || !formData.lastName || !normalizeLocalPhone(val('phone')) || !formData.country || !formData.terms){
        alert('Veuillez remplir tous les champs obligatoires et accepter les conditions.');
        return false;
    }

    if(!photoData){
        alert('La photo de profil est obligatoire pour générer la carte.');
        return false;
    }

    return true;
}

// ----------------------------
// Overlay de chargement
// ----------------------------
function showLoader(title='Traitement en cours…', sub='Merci de patienter.'){
    const o = byId('paymentOverlay');
    if(!o) return;
    o.classList.remove('hidden');
    byId('paymentOverlayTitle').textContent = title;
    byId('paymentOverlaySub').textContent = sub;
}

function hideLoader() {
    byId('paymentOverlay')?.classList.add('hidden');
}

// ----------------------------
// Finalisation de l’adhésion
// ----------------------------
async function confirmMembership() {
    try {
        showLoader('Finalisation de votre adhésion…','Nous générons votre carte membre.');

        const payload = new URLSearchParams();
        payload.append('firstName', formData.firstName);
        payload.append('lastName', formData.lastName);
        payload.append('email', formData.email);
        payload.append('phone', formData.phone);
        payload.append('country', formData.country);
        payload.append('birthDate', formData.birthDate);
        payload.append('plan', selectedPlan);
        if(photoData) payload.append('photoData', photoData);
        if(lastTransactionId) payload.append('transactionId', lastTransactionId);
        if(lastTransactionStatus) payload.append('transactionStatus', lastTransactionStatus);
        payload.append('amount', amountForSelectedPlan());

        const res = await fetch('/membership-submit', { // Remplacer par path('app_membership_submit')
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: payload.toString()
        });

        const data = await res.json();
        if(!res.ok || !data.ok) throw new Error(data.message||'Erreur lors de l\'inscription');

        let finalUrl = data.redirect || (data.cardUrl + '?plan=' + encodeURIComponent(selectedPlan));
        if(data.avatar) finalUrl += '&avatar=' + encodeURIComponent(data.avatar);

        window.location.href = finalUrl;

    } catch(e){
        hideLoader();
        alert('Impossible de finaliser l\'adhésion: ' + e.message);
    }
}

// ----------------------------
// Intégration FedaPay
// ----------------------------
const FEDAPAY_PK = window.fedapay_public_key || ''; // Injecter depuis Twig

function prepareFedaPay() {
    const amount = amountForSelectedPlan();
    const desc = 'Adhésion Binajia';

    FedaPay.init('#pay-btn', {
        public_key: FEDAPAY_PK,
        transaction: { amount, currency: 'XOF', description: desc },
        customer: {
            email: formData.email,
            firstname: formData.firstName,
            lastname: formData.lastName,
            phone_number: { number: formData.phone.replace('+',''), country: (formData.country||'').toUpperCase() }
        },
        callback: onFedaPayResult,
        onComplete: onFedaPayResult,
        onclose: () => {
            if(!callbackFired && lastTransactionId){
                fetch(`/payment-verify?tx=${encodeURIComponent(lastTransactionId)}`)
                    .then(r => r.json())
                    .then(v => {
                        if(v.ok && ['approved','succeeded','success','paid'].includes(String(v.status||'').toLowerCase())) confirmMembership();
                        else hideLoader();
                    }).catch(() => hideLoader());
            } else hideLoader();
        }
    });
    fedapayReady = true;
}

function onFedaPayResult(response){
    callbackFired = true;
    if(response?.transaction){
        lastTransactionId = response.transaction.id || lastTransactionId;
        lastTransactionStatus = response.transaction.status || lastTransactionStatus;
    }

    if(lastTransactionId){
        fetch(`/payment-verify?tx=${encodeURIComponent(lastTransactionId)}`)
            .then(r=>r.json())
            .then(v => {
                if(v.ok && ['approved','succeeded','success','paid'].includes(String(v.status||'').toLowerCase())) confirmMembership();
                else hideLoader();
            })
            .catch(() => hideLoader());
    } else hideLoader();
}

// ----------------------------
// Bouton de paiement
// ----------------------------
document.getElementById('pay-btn')?.addEventListener('click', async e => {
    e.preventDefault();
    if(!(await validateAndContinue())) return;

    if(!fedapayReady) prepareFedaPay();
    if(typeof FedaPay.open === 'function') FedaPay.open();
});
