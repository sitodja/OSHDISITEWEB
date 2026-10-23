/* ==============================================================
   OSHDI - MOTEUR DE RECHERCHE LOCALITÉS, VILLES, RÉGIONS & ACTIVITÉS
   Filtrage dynamique instantané des filiales, succursales et représentations
   ============================================================== */

document.addEventListener('DOMContentLoaded', () => {
    initBranchSearch();
});

function initBranchSearch() {
    const searchInput = document.getElementById('searchQueryInput');
    const regionSelect = document.getElementById('searchRegionSelect');
    const typeSelect = document.getElementById('searchTypeSelect');
    const branchCards = document.querySelectorAll('.branch-card');
    const resultCount = document.getElementById('searchResultCount');
    const noResultsMsg = document.getElementById('noResultsMessage');

    if (!searchInput && !regionSelect && !typeSelect) return;

    function filterBranches() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selectedRegion = (regionSelect ? regionSelect.value : '').toLowerCase().trim();
        const selectedType = (typeSelect ? typeSelect.value : '').toLowerCase().trim();

        let visibleCount = 0;

        branchCards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            const location = card.getAttribute('data-location') || '';
            const city = card.getAttribute('data-city') || '';
            const department = card.getAttribute('data-department') || '';
            const region = card.getAttribute('data-region') || '';
            const type = card.getAttribute('data-type') || '';
            const activities = card.getAttribute('data-activities') || '';

            const allText = `${name} ${location} ${city} ${department} ${region} ${type} ${activities}`.toLowerCase();

            const matchesQuery = !query || allText.includes(query);
            const matchesRegion = !selectedRegion || region.toLowerCase().includes(selectedRegion);
            const matchesType = !selectedType || type.toLowerCase().includes(selectedType);

            if (matchesQuery && matchesRegion && matchesType) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.3s ease';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (resultCount) {
            resultCount.innerText = visibleCount;
        }

        if (noResultsMsg) {
            noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterBranches);
    if (regionSelect) regionSelect.addEventListener('change', filterBranches);
    if (typeSelect) typeSelect.addEventListener('change', filterBranches);
}
