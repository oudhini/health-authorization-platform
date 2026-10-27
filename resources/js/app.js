import 'bootstrap/js/dist/collapse';

const coverageSelect = document.querySelector('#coverage_selection');
const relatedRequestSelect = document.querySelector('#related_request_id');
const relatedRequestHelp = document.querySelector('#related-request-help');

if (coverageSelect instanceof HTMLSelectElement
    && relatedRequestSelect instanceof HTMLSelectElement
    && relatedRequestHelp instanceof HTMLElement) {
    const updateRelatedRequests = () => {
        const beneficiaryId = coverageSelect.selectedOptions[0]?.dataset.beneficiaryId;
        let hasMatchingRequest = false;

        Array.from(relatedRequestSelect.options).forEach((option) => {
            if (option.value === '') {
                return;
            }

            const matchesBeneficiary = beneficiaryId !== undefined
                && option.dataset.beneficiaryId === beneficiaryId;

            option.hidden = !matchesBeneficiary;
            option.disabled = !matchesBeneficiary;
            hasMatchingRequest ||= matchesBeneficiary;
        });

        if (relatedRequestSelect.selectedOptions[0]?.disabled) {
            relatedRequestSelect.value = '';
        }

        relatedRequestSelect.disabled = beneficiaryId === undefined;
        relatedRequestHelp.textContent = beneficiaryId === undefined
            ? 'Sélectionnez d’abord un bénéficiaire pour afficher ses consultations approuvées.'
            : hasMatchingRequest
                ? 'Seules les consultations approuvées de ce bénéficiaire sont proposées.'
                : 'Aucune consultation approuvée trouvée pour ce bénéficiaire.';
    };

    coverageSelect.addEventListener('change', updateRelatedRequests);
    updateRelatedRequests();
}
