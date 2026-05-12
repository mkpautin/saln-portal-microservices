<template>
  <div id="salnFormApp" class="container">
    <input type="hidden" id="saln-initial-data" :value="initialDataEncoded">

    <div class="actions actions-header">
      <h1>SALN Form</h1>
      <div class="actions top-actions header-actions">
        <span id="autosaveStatus" class="autosave-chip autosave-idle">All changes saved</span>
        <button type="button" id="salnGeneratePdfButton" class="btn-dark">Generate PDF</button>
        <button type="button" class="btn-dark" @click="exportJson">Export JSON</button>
        <button type="button" id="salnImportButton" class="btn-dark">Import JSON</button>

        <form id="salnImportForm" enctype="multipart/form-data" hidden>
          <input type="file" id="salnImportInput" name="import_file" accept=".json,application/json">
        </form>
      </div>
    </div>

    <form
      id="salnSaveForm"
      method="POST"
      action="/api/saln"
      data-draft-url="/api/saln/draft"
      data-pdf-url="/api/saln/pdf"
    >
      <div class="section">
        <h2>Compliance for</h2>
        <div class="compliance-option" data-compliance-option="assumption">
          <div class="compliance-row">
            <input
              class="compliance-radio"
              type="radio"
              name="compliance_type"
              value="assumption"
              v-model="form.compliance_type"
            >
            <label>Assumption of office as of</label>
          </div>
          <input
            class="compliance-input"
            data-compliance-target="assumption"
            type="date"
            name="assumption_date"
            v-model="form.assumption_date"
          >
        </div>

        <div class="compliance-option" data-compliance-option="annual">
          <div class="compliance-row">
            <input
              class="compliance-radio"
              type="radio"
              name="compliance_type"
              value="annual"
              v-model="form.compliance_type"
            >
            <label>Annual filing as of December 31,</label>
          </div>
          <input
            class="compliance-input"
            data-compliance-target="annual"
            type="number"
            name="annual_year"
            min="1900"
            max="2100"
            placeholder="Year"
            v-model="form.annual_year"
          >
        </div>

        <div class="compliance-option" data-compliance-option="exit">
          <div class="compliance-row">
            <input
              class="compliance-radio"
              type="radio"
              name="compliance_type"
              value="exit"
              v-model="form.compliance_type"
            >
            <label>Exit as of</label>
          </div>
          <input
            class="compliance-input"
            data-compliance-target="exit"
            type="date"
            name="exit_date"
            v-model="form.exit_date"
          >
        </div>
      </div>

      <div class="section">
        <h2>Declarant</h2>
        <div class="row">
          <div class="form-row"><label>Family Name</label><input name="declarant[family_name]" v-model="form.declarant.family_name"></div>
          <div class="form-row"><label>First Name</label><input name="declarant[first_name]" v-model="form.declarant.first_name"></div>
          <div class="form-row"><label>Middle Initial (Optional)</label><input name="declarant[middle_initial]" v-model="form.declarant.middle_initial"></div>
          <div class="form-row"><label>Position</label><input name="declarant[position]" v-model="form.declarant.position"></div>
          <div class="form-row"><label>Agency/Office</label><input name="declarant[agency_office]" v-model="form.declarant.agency_office"></div>
          <div class="form-row"><label>Office Address</label><input name="declarant[office_address]" v-model="form.declarant.office_address"></div>
        </div>
      </div>

      <div class="section">
        <h2>Spouse</h2>
        <div class="row">
          <div class="form-row"><label>Family Name</label><input name="spouse[family_name]" v-model="form.spouse.family_name"></div>
          <div class="form-row"><label>First Name</label><input name="spouse[first_name]" v-model="form.spouse.first_name"></div>
          <div class="form-row"><label>Middle Initial (Optional)</label><input name="spouse[middle_initial]" v-model="form.spouse.middle_initial"></div>
          <div class="form-row"><label>Position</label><input name="spouse[position]" v-model="form.spouse.position"></div>
          <div class="form-row"><label>Agency/Office</label><input name="spouse[agency_office]" v-model="form.spouse.agency_office"></div>
          <div class="form-row"><label>Office Address</label><input name="spouse[office_address]" v-model="form.spouse.office_address"></div>
        </div>
      </div>

      <div class="section">
        <h2>Joint/Separate Filing</h2>
        <div class="inline">
          <label><input type="radio" name="filing_type" value="joint" v-model="form.filing_type"> Joint Filing</label>
          <label><input type="radio" name="filing_type" value="separate" v-model="form.filing_type"> Separate Filing</label>
          <label><input type="radio" name="filing_type" value="not_applicable" v-model="form.filing_type"> Not Applicable</label>
        </div>
      </div>

      <div class="section">
        <h2>Additional Spouses</h2>
        <div id="additionalSpousesContainer"></div>
        <button type="button" id="addSpouseBtn">Add Spouse</button>
      </div>

      <div class="section">
        <h2>Children</h2>
        <div id="childrenContainer"></div>
        <button type="button" id="addChildBtn">Add Child</button>
      </div>

      <div class="section">
        <h2>Assets</h2>

        <h3>Real Properties</h3>
        <div id="realPropertiesContainer"></div>
        <button type="button" id="addRealPropertyBtn">Add Real Property</button>
        <div class="summary">Subtotal (Real Properties): <span id="realSubtotal">0.00</span></div>

        <h3 class="personal-properties-heading">Personal Properties</h3>
        <div id="personalPropertiesContainer"></div>
        <button type="button" id="addPersonalPropertyBtn">Add Personal Property</button>
        <div class="summary">Subtotal (Personal Properties): <span id="personalSubtotal">0.00</span></div>

        <div class="summary total-assets-summary">Total Assets: <span id="totalAssets">0.00</span></div>
      </div>

      <div class="section">
        <h2>Liabilities</h2>
        <div id="liabilitiesContainer"></div>
        <button type="button" id="addLiabilityBtn">Add Liability</button>
        <div class="summary">Total Liabilities: <span id="totalLiabilities">0.00</span></div>
      </div>

      <div class="section">
        <h2>Net Worth</h2>
        <div class="summary">Net Worth: <span id="netWorth">0.00</span></div>
      </div>

      <div class="section">
        <h2>Business Interests and Financial Connections</h2>
        <div id="businessInterestsContainer"></div>
        <button type="button" id="addBusinessInterestBtn">Add Business Interest</button>
      </div>

      <div class="section">
        <h2>Relatives in the Government Service</h2>
        <div id="relativesInGovernmentServiceContainer"></div>
        <button type="button" id="addRelativeInGovernmentServiceBtn">Add Relative</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import salnApi from '../services/salnApi';

const form = reactive({
  compliance_type: 'assumption',
  assumption_date: '',
  annual_year: '',
  exit_date: '',
  declarant: {
    family_name: '',
    first_name: '',
    middle_initial: '',
    position: '',
    agency_office: '',
    office_address: '',
  },
  spouse: {
    family_name: '',
    first_name: '',
    middle_initial: '',
    position: '',
    agency_office: '',
    office_address: '',
  },
  filing_type: 'joint',
  additional_spouses: [],
  children: [],
  real_properties: [],
  personal_properties: [],
  business_interests: [],
  relatives_in_government_service: [],
  liabilities: [],
});

const initialDataEncoded = computed(() => {
  const payload = {
    additionalSpouses: form.additional_spouses,
    children: form.children,
    realProperties: form.real_properties,
    personalProperties: form.personal_properties,
    businessInterests: form.business_interests,
    relativesInGovernmentService: form.relatives_in_government_service,
    liabilities: form.liabilities,
  };

  return btoa(JSON.stringify(payload));
});

async function loadFormData() {
  let response;

  try {
    response = await salnApi.get('/saln');
  } catch (_error) {
    return;
  }

  const payload = response.data || {};
  const data = payload.data || {};

  applyPayload(data);
}

function applyPayload(data) {
  const payload = data || {};

  form.compliance_type = payload.compliance_type || 'assumption';
  form.assumption_date = payload.assumption_date || '';
  form.annual_year = payload.annual_year || '';
  form.exit_date = payload.exit_date || '';
  form.declarant = { ...form.declarant, ...(payload.declarant || {}) };
  form.spouse = { ...form.spouse, ...(payload.spouse || {}) };
  form.filing_type = payload.filing_type || 'joint';
  form.additional_spouses = payload.additional_spouses || [];
  form.children = payload.children || [];
  form.real_properties = payload.real_properties || [];
  form.personal_properties = payload.personal_properties || [];
  form.business_interests = payload.business_interests || [];
  form.relatives_in_government_service = payload.relatives_in_government_service || [];
  form.liabilities = payload.liabilities || [];
}

function parseFormFieldName(name) {
  return name.split(/\[|\]/).filter(Boolean);
}

function assignFormValue(target, name, value) {
  const keys = parseFormFieldName(name);

  if (!keys.length) {
    return;
  }

  keys.reduce((cursor, key, index) => {
    const isLast = index === keys.length - 1;

    if (isLast) {
      cursor[key] = value;
      return cursor;
    }

    const nextKey = keys[index + 1];

    if (!cursor[key]) {
      cursor[key] = /^\d+$/.test(nextKey) ? [] : {};
    }

    return cursor[key];
  }, target);
}

function currentFormPayload() {
  const payload = {
    compliance_type: form.compliance_type || 'assumption',
    assumption_date: form.assumption_date || '',
    annual_year: form.annual_year || '',
    exit_date: form.exit_date || '',
    declarant: {},
    spouse: {},
    filing_type: form.filing_type || 'joint',
    additional_spouses: [],
    children: [],
    real_properties: [],
    personal_properties: [],
    business_interests: [],
    relatives_in_government_service: [],
    liabilities: [],
  };

  const saveForm = document.getElementById('salnSaveForm');

  if (saveForm) {
    const formData = new FormData(saveForm);

    for (const [name, value] of formData.entries()) {
      assignFormValue(payload, name, value);
    }
  }

  return payload;
}

function exportJson() {
  const blob = new Blob([JSON.stringify(currentFormPayload(), null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = 'saln-progress.json';
  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();
  URL.revokeObjectURL(url);
}

function initSalnForm() {
  const formContainer = document.getElementById('salnFormApp');

  if (!formContainer) {
    return;
  }

  const saveForm = document.getElementById('salnSaveForm');
  const draftUrl = toSalnPath(saveForm?.dataset?.draftUrl || '/saln/draft');
  const pdfUrl = toSalnPath(saveForm?.dataset?.pdfUrl || '/saln/pdf');
    function toSalnPath(url) {
      if (!url) {
        return '';
      }

      try {
        const parsed = new URL(url);
        return parsed.pathname.replace(/^\/api/, '');
      } catch (_error) {
        return url.replace(/^\/api/, '');
      }
    }

    function resolveDownloadPath(url) {
      if (!url) {
        return '';
      }

      try {
        const parsed = new URL(url);
        return parsed.pathname.replace(/^\/api/, '');
      } catch (_error) {
        return url.replace(/^\/api/, '');
      }
    }

  const initialDataInput = document.getElementById('saln-initial-data');
  const importButton = document.getElementById('salnImportButton');
  const importInput = document.getElementById('salnImportInput');
  const generatePdfButton = document.getElementById('salnGeneratePdfButton');
  const autosaveStatus = document.getElementById('autosaveStatus');

  const additionalSpousesContainer = document.getElementById('additionalSpousesContainer');
  const childrenContainer = document.getElementById('childrenContainer');
  const realPropertiesContainer = document.getElementById('realPropertiesContainer');
  const personalPropertiesContainer = document.getElementById('personalPropertiesContainer');
  const businessInterestsContainer = document.getElementById('businessInterestsContainer');
  const relativesInGovernmentServiceContainer = document.getElementById('relativesInGovernmentServiceContainer');
  const liabilitiesContainer = document.getElementById('liabilitiesContainer');

  const counters = {
    spouse: 0,
    child: 0,
    real: 0,
    personal: 0,
    business: 0,
    relative: 0,
    liability: 0,
  };

  function loadInitialData() {
    const fallback = {
      additionalSpouses: [],
      children: [],
      realProperties: [],
      personalProperties: [],
      businessInterests: [],
      relativesInGovernmentService: [],
      liabilities: [],
    };

    if (!initialDataInput || !initialDataInput.value) {
      return fallback;
    }

    try {
      return {
        ...fallback,
        ...JSON.parse(atob(initialDataInput.value)),
      };
    } catch (_error) {
      return fallback;
    }
  }

  const initialData = loadInitialData();

  function resetDynamicRows() {
    if (additionalSpousesContainer) {
      additionalSpousesContainer.innerHTML = '';
    }
    if (childrenContainer) {
      childrenContainer.innerHTML = '';
    }
    if (realPropertiesContainer) {
      realPropertiesContainer.innerHTML = '';
    }
    if (personalPropertiesContainer) {
      personalPropertiesContainer.innerHTML = '';
    }
    if (businessInterestsContainer) {
      businessInterestsContainer.innerHTML = '';
    }
    if (relativesInGovernmentServiceContainer) {
      relativesInGovernmentServiceContainer.innerHTML = '';
    }
    if (liabilitiesContainer) {
      liabilitiesContainer.innerHTML = '';
    }

    counters.spouse = 0;
    counters.child = 0;
    counters.real = 0;
    counters.personal = 0;
    counters.business = 0;
    counters.relative = 0;
    counters.liability = 0;
  }

  function rebuildRowsFromForm() {
    (form.additional_spouses || []).forEach(function (row) { addAdditionalSpouseRow(row); });
    (form.children || []).forEach(function (row) { addChildRow(row); });
    (form.real_properties || []).forEach(function (row) { addRealPropertyRow(row); });
    (form.personal_properties || []).forEach(function (row) { addPersonalPropertyRow(row); });
    (form.business_interests || []).forEach(function (row) { addBusinessInterestRow(row); });
    (form.relatives_in_government_service || []).forEach(function (row) { addRelativeInGovernmentServiceRow(row); });
    (form.liabilities || []).forEach(function (row) { addLiabilityRow(row); });
  }

  function toNumber(value) {
    const parsed = parseFloat(value);
    return Number.isFinite(parsed) ? parsed : 0;
  }

  function formatMoney(value) {
    return value.toFixed(2);
  }

  function createRemoveButton(wrapper) {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = 'Remove';
    button.addEventListener('click', function () {
      wrapper.remove();
      updateTotals();
      scheduleDraftSave();
    });
    return button;
  }

  function ownerScopeField(name, selectedValue) {
    const spouseChildrenOwned = selectedValue === 'spouse_children';

    return `
            <div class="owner-scope-row">
                <input type="hidden" name="${name}" value="declarant">
                <label class="owner-scope-checkbox">
                    <input type="checkbox" name="${name}" value="spouse_children" ${spouseChildrenOwned ? 'checked' : ''}>
                    Owned by spouse / children
                </label>
            </div>
        `;
  }

  function calculateAge(dateStr) {
    if (!dateStr) {
      return '';
    }

    const birthDate = new Date(dateStr);
    if (Number.isNaN(birthDate.getTime())) {
      return '';
    }

    const now = new Date();
    let age = now.getFullYear() - birthDate.getFullYear();
    const monthDiff = now.getMonth() - birthDate.getMonth();
    const dayDiff = now.getDate() - birthDate.getDate();

    if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
      age -= 1;
    }

    return age >= 0 ? String(age) : '';
  }

  function addAdditionalSpouseRow(data = {}) {
    const index = counters.spouse++;
    const wrapper = document.createElement('div');
    wrapper.className = 'list-item';
    wrapper.innerHTML = `
            <div class="form-row">
                <label>Name</label>
                <input name="additional_spouses[${index}][name]" value="${data.name || ''}">
            </div>
        `;
    wrapper.appendChild(createRemoveButton(wrapper));
    additionalSpousesContainer.appendChild(wrapper);
  }

  function addChildRow(data = {}) {
    const index = counters.child++;
    const wrapper = document.createElement('div');
    wrapper.className = 'list-item';
    wrapper.innerHTML = `
            <div class="row">
                <div class="form-row">
                    <label>Name</label>
                    <input name="children[${index}][name]" value="${data.name || ''}">
                </div>
                <div class="form-row">
                    <label>Date of Birth</label>
                    <input class="child-dob" type="date" name="children[${index}][date_of_birth]" value="${data.date_of_birth || ''}">
                </div>
            </div>
            <div class="form-row">
                <label>Age (Auto)</label>
                <div class="child-age"></div>
            </div>
        `;
    wrapper.appendChild(createRemoveButton(wrapper));
    childrenContainer.appendChild(wrapper);

    const dobInput = wrapper.querySelector('.child-dob');
    const ageTarget = wrapper.querySelector('.child-age');
    ageTarget.textContent = calculateAge(dobInput.value);

    dobInput.addEventListener('input', function () {
      ageTarget.textContent = calculateAge(dobInput.value);
    });
  }

  function addRealPropertyRow(data = {}) {
    const index = counters.real++;
    const wrapper = document.createElement('div');
    wrapper.className = 'list-item real-property-row';
    wrapper.innerHTML = `
            <div class="row">
                <div class="form-row"><label>Description</label><input name="real_properties[${index}][description]" value="${data.description || ''}"></div>
                <div class="form-row"><label>Kind</label><input name="real_properties[${index}][kind]" value="${data.kind || ''}"></div>
                <div class="form-row"><label>Exact Location</label><input name="real_properties[${index}][exact_location]" value="${data.exact_location || ''}"></div>
                <div class="form-row"><label>Assessed Value</label><input type="number" step="0.01" min="0" class="real-cost" name="real_properties[${index}][assessed_value]" value="${data.assessed_value || ''}"></div>
                <div class="form-row"><label>Current Fair Market Value</label><input type="number" step="0.01" min="0" name="real_properties[${index}][current_fair_market_value]" value="${data.current_fair_market_value || ''}"></div>
                <div class="form-row"><label>Year of Acquisition</label><input type="number" min="1900" max="2100" name="real_properties[${index}][year_of_acquisition]" value="${data.year_of_acquisition || ''}"></div>
                <div class="form-row"><label>Mode of Acquisition</label><input name="real_properties[${index}][mode_of_acquisition]" value="${data.mode_of_acquisition || ''}"></div>
                <div class="form-row"><label>Acquisition Cost</label><input type="number" step="0.01" min="0" name="real_properties[${index}][acquisition_cost]" value="${data.acquisition_cost || ''}"></div>
                ${ownerScopeField(`real_properties[${index}][owner_scope]`, data.owner_scope)}
            </div>
        `;
    wrapper.appendChild(createRemoveButton(wrapper));
    realPropertiesContainer.appendChild(wrapper);
  }

  function addPersonalPropertyRow(data = {}) {
    const index = counters.personal++;
    const wrapper = document.createElement('div');
    wrapper.className = 'list-item personal-property-row';
    wrapper.innerHTML = `
            <div class="row">
                <div class="form-row"><label>Description</label><input name="personal_properties[${index}][description]" value="${data.description || ''}"></div>
                <div class="form-row"><label>Acquisition Year</label><input type="number" min="1900" max="2100" name="personal_properties[${index}][acquisition_year]" value="${data.acquisition_year || ''}"></div>
                <div class="form-row"><label>Acquisition Cost / Amount</label><input type="number" step="0.01" min="0" class="personal-cost" name="personal_properties[${index}][acquisition_cost_amount]" value="${data.acquisition_cost_amount || ''}"></div>
                ${ownerScopeField(`personal_properties[${index}][owner_scope]`, data.owner_scope)}
            </div>
        `;
    wrapper.appendChild(createRemoveButton(wrapper));
    personalPropertiesContainer.appendChild(wrapper);
  }

  function addBusinessInterestRow(data = {}) {
    const index = counters.business++;
    const wrapper = document.createElement('div');
    wrapper.className = 'list-item business-interest-row';
    wrapper.innerHTML = `
            <div class="row">
                <div class="form-row"><label>Name of Entity / Business Enterprise</label><input name="business_interests[${index}][name_of_entity_or_business_enterprise]" value="${data.name_of_entity_or_business_enterprise || ''}"></div>
                <div class="form-row"><label>Business Address</label><input name="business_interests[${index}][business_address]" value="${data.business_address || ''}"></div>
                <div class="form-row"><label>Nature of Business Interest / Financial Connection</label><input name="business_interests[${index}][nature_of_business_interest_or_financial_connection]" value="${data.nature_of_business_interest_or_financial_connection || ''}"></div>
                <div class="form-row"><label>Date of Acquisition of Interest or Connection</label><input type="date" name="business_interests[${index}][date_of_acquisition]" value="${data.date_of_acquisition || ''}"></div>
                ${ownerScopeField(`business_interests[${index}][owner_scope]`, data.owner_scope)}
            </div>
        `;
    wrapper.appendChild(createRemoveButton(wrapper));
    businessInterestsContainer.appendChild(wrapper);
  }

  function addRelativeInGovernmentServiceRow(data = {}) {
    const index = counters.relative++;
    const wrapper = document.createElement('div');
    wrapper.className = 'list-item relative-government-row';
    wrapper.innerHTML = `
            <div class="row">
                <div class="form-row"><label>Name of Relative</label><input name="relatives_in_government_service[${index}][name_of_relative]" value="${data.name_of_relative || ''}"></div>
                <div class="form-row"><label>Relationship</label><input name="relatives_in_government_service[${index}][relationship]" value="${data.relationship || ''}"></div>
                <div class="form-row"><label>Position</label><input name="relatives_in_government_service[${index}][position]" value="${data.position || ''}"></div>
                <div class="form-row"><label>Name of Agency / Office and Address</label><input name="relatives_in_government_service[${index}][name_of_agency_office_and_address]" value="${data.name_of_agency_office_and_address || ''}"></div>
            </div>
        `;
    wrapper.appendChild(createRemoveButton(wrapper));
    relativesInGovernmentServiceContainer.appendChild(wrapper);
  }

  function addLiabilityRow(data = {}) {
    const index = counters.liability++;
    const wrapper = document.createElement('div');
    wrapper.className = 'list-item liability-row';
    wrapper.innerHTML = `
            <div class="row">
                <div class="form-row"><label>Nature</label><input name="liabilities[${index}][nature]" value="${data.nature || ''}"></div>
                <div class="form-row"><label>Name of Creditor</label><input name="liabilities[${index}][name_of_creditor]" value="${data.name_of_creditor || ''}"></div>
                <div class="form-row"><label>Outstanding Balance</label><input type="number" step="0.01" min="0" class="liability-balance" name="liabilities[${index}][outstanding_balance]" value="${data.outstanding_balance || ''}"></div>
                ${ownerScopeField(`liabilities[${index}][owner_scope]`, data.owner_scope)}
            </div>
        `;
    wrapper.appendChild(createRemoveButton(wrapper));
    liabilitiesContainer.appendChild(wrapper);
  }

  function updateTotals() {
    let realSubtotal = 0;
    let personalSubtotal = 0;
    let liabilitiesTotal = 0;

    document.querySelectorAll('.real-cost').forEach(function (input) {
      realSubtotal += toNumber(input.value);
    });

    document.querySelectorAll('.personal-cost').forEach(function (input) {
      personalSubtotal += toNumber(input.value);
    });

    document.querySelectorAll('.liability-balance').forEach(function (input) {
      liabilitiesTotal += toNumber(input.value);
    });

    const totalAssets = realSubtotal + personalSubtotal;
    const netWorth = totalAssets - liabilitiesTotal;

    document.getElementById('realSubtotal').textContent = formatMoney(realSubtotal);
    document.getElementById('personalSubtotal').textContent = formatMoney(personalSubtotal);
    document.getElementById('totalAssets').textContent = formatMoney(totalAssets);
    document.getElementById('totalLiabilities').textContent = formatMoney(liabilitiesTotal);
    document.getElementById('netWorth').textContent = formatMoney(netWorth);
  }

  function updateComplianceInputs() {
    const selectedRadio = document.querySelector('.compliance-radio:checked');
    const selectedValue = selectedRadio ? selectedRadio.value : '';

    document.querySelectorAll('.compliance-option').forEach(function (option) {
      const optionValue = option.getAttribute('data-compliance-option');
      const input = option.querySelector('.compliance-input');

      if (!input) {
        return;
      }

      const isSelected = optionValue === selectedValue;
      input.disabled = !isSelected;
      input.required = isSelected;
    });
  }

  function createDraftRequestBody() {
    return new FormData(saveForm);
  }

  let draftTimer = null;
  let draftInFlight = null;
  let draftRequestSeq = 0;

  function setAutosaveState(state, message) {
    if (!autosaveStatus) {
      return;
    }

    autosaveStatus.classList.remove(
      'autosave-idle',
      'autosave-dirty',
      'autosave-saving',
      'autosave-saved',
      'autosave-error'
    );

    autosaveStatus.classList.add(`autosave-${state}`);
    autosaveStatus.textContent = message;
  }

  function savedAtMessage() {
    const now = new Date();
    const time = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    return `Saved at ${time}`;
  }

  function persistDraft() {
    if (!saveForm) {
      return Promise.resolve();
    }

    const requestSeq = ++draftRequestSeq;
    setAutosaveState('saving', 'Saving...');

    const body = createDraftRequestBody();
    const request = salnApi.post(draftUrl, body, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    }).then(function () {
      if (requestSeq === draftRequestSeq) {
        setAutosaveState('saved', savedAtMessage());
      }

      return true;
    }).catch(function () {
      if (requestSeq === draftRequestSeq) {
        setAutosaveState('error', 'Autosave failed. Continuing to retry.');
      }

      return null;
    }).finally(function () {
      if (draftInFlight === request) {
        draftInFlight = null;
      }
    });

    draftInFlight = request;
    return request;
  }

  function scheduleDraftSave() {
    if (draftTimer) {
      window.clearTimeout(draftTimer);
    }

    setAutosaveState('dirty', 'Unsaved changes');

    draftTimer = window.setTimeout(function () {
      draftTimer = null;
      persistDraft();
    }, 500);
  }

  function triggerBrowserDownload(blob, fileName) {
    const downloadUrl = window.URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = downloadUrl;
    anchor.download = fileName;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.URL.revokeObjectURL(downloadUrl);
  }

  function resolveDownloadFilename(response) {
    const disposition = response.headers.get('content-disposition') || '';
    const match = disposition.match(/filename="([^"]+)"/i);

    if (match && match[1]) {
      return match[1];
    }

    return 'saln.pdf';
  }

  async function extractErrorMessage(error) {
    const payload = error?.response?.data;

    if (payload?.message) {
      return payload.message;
    }

    if (payload?.errors && typeof payload.errors === 'object') {
      const firstErrorKey = Object.keys(payload.errors)[0];
      if (firstErrorKey && Array.isArray(payload.errors[firstErrorKey]) && payload.errors[firstErrorKey][0]) {
        return payload.errors[firstErrorKey][0];
      }
    }

    return 'PDF generation failed. Please try again.';
  }

  async function generatePdf() {
    if (generatePdfButton) {
      generatePdfButton.disabled = true;
    }

    try {
      if (draftTimer) {
        window.clearTimeout(draftTimer);
        draftTimer = null;
      }

      if (draftInFlight) {
        await draftInFlight;
      }

      const saved = await persistDraft();
      if (!saved) {
        throw new Error('Autosave failed. Please try again before generating PDF.');
      }

      setAutosaveState('saving', 'Generating PDF...');

      let payload;

      try {
        const response = await salnApi.post(pdfUrl, createDraftRequestBody(), {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });
        payload = response.data;
      } catch (error) {
        throw new Error(await extractErrorMessage(error));
      }

      if (!payload.download_url) {
        throw new Error('PDF download URL missing.');
      }

      const downloadPath = resolveDownloadPath(payload.download_url);
      const downloadResponse = await salnApi.get(downloadPath, {
        responseType: 'blob',
        headers: {
          Accept: 'application/pdf',
        },
      });

      const disposition = downloadResponse.headers?.['content-disposition'];
      const fakeResponse = {
        headers: {
          get: (name) => (name === 'content-disposition' ? disposition : null),
        },
      };

      triggerBrowserDownload(downloadResponse.data, resolveDownloadFilename(fakeResponse));
      setAutosaveState('saved', 'PDF generated successfully.');
    } catch (error) {
      setAutosaveState('error', error instanceof Error ? error.message : 'PDF generation failed. Please try again.');
    } finally {
      if (generatePdfButton) {
        generatePdfButton.disabled = false;
      }
    }
  }

  document.getElementById('addSpouseBtn').addEventListener('click', function () { addAdditionalSpouseRow(); });
  document.getElementById('addChildBtn').addEventListener('click', function () { addChildRow(); });
  document.getElementById('addRealPropertyBtn').addEventListener('click', function () { addRealPropertyRow(); updateTotals(); scheduleDraftSave(); });
  document.getElementById('addPersonalPropertyBtn').addEventListener('click', function () { addPersonalPropertyRow(); updateTotals(); scheduleDraftSave(); });
  document.getElementById('addBusinessInterestBtn').addEventListener('click', function () { addBusinessInterestRow(); scheduleDraftSave(); });
  document.getElementById('addRelativeInGovernmentServiceBtn').addEventListener('click', function () { addRelativeInGovernmentServiceRow(); scheduleDraftSave(); });
  document.getElementById('addLiabilityBtn').addEventListener('click', function () { addLiabilityRow(); updateTotals(); scheduleDraftSave(); });

  document.addEventListener('input', function (event) {
    if (event.target.classList.contains('real-cost') || event.target.classList.contains('personal-cost') || event.target.classList.contains('liability-balance')) {
      updateTotals();
    }

    if (event.target.closest('#salnSaveForm')) {
      scheduleDraftSave();
    }
  });

  document.addEventListener('change', function (event) {
    if (event.target.closest('#salnSaveForm')) {
      scheduleDraftSave();
    }
  });

  document.querySelectorAll('.compliance-radio').forEach(function (radio) {
    radio.addEventListener('change', updateComplianceInputs);
  });

  if (importButton && importInput) {
    importButton.addEventListener('click', function () {
      importInput.click();
    });

    importInput.addEventListener('change', function () {
      const file = importInput.files && importInput.files[0];

      if (!file) {
        return;
      }

      const payload = new FormData();
      payload.append('import_file', file);

      setAutosaveState('saving', 'Importing JSON...');

      salnApi
        .post('/saln/import', payload, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        .then((response) => response.data)
        .then((responsePayload) => {
          const data = responsePayload?.data || {};
          applyPayload(data);
          resetDynamicRows();
          rebuildRowsFromForm();
          updateComplianceInputs();
          updateTotals();
          setAutosaveState('saved', 'SALN JSON imported successfully.');
        })
        .catch((error) => {
          const message = error?.response?.data?.message || 'Import failed. Please check the file and try again.';
          setAutosaveState('error', message);
        })
        .finally(() => {
          importInput.value = '';
        });
    });
  }

  if (generatePdfButton) {
    generatePdfButton.addEventListener('click', function () {
      void generatePdf();
    });
  }

  (initialData.additionalSpouses || []).forEach(function (row) { addAdditionalSpouseRow(row); });
  (initialData.children || []).forEach(function (row) { addChildRow(row); });
  (initialData.realProperties || []).forEach(function (row) { addRealPropertyRow(row); });
  (initialData.personalProperties || []).forEach(function (row) { addPersonalPropertyRow(row); });
  (initialData.businessInterests || []).forEach(function (row) { addBusinessInterestRow(row); });
  (initialData.relativesInGovernmentService || []).forEach(function (row) { addRelativeInGovernmentServiceRow(row); });
  (initialData.liabilities || []).forEach(function (row) { addLiabilityRow(row); });

  updateComplianceInputs();
  updateTotals();

  document.addEventListener('pagehide', function () {
    if (draftTimer) {
      window.clearTimeout(draftTimer);
      draftTimer = null;
    }

    if (draftInFlight) {
      return;
    }

    void persistDraft();
  });
}

onMounted(async () => {
  await loadFormData();
  initSalnForm();
});
</script>

<style>
:root {
  --bg-1: #e8f6f1;
  --bg-2: #ffffff;
  --surface: #ffffff;
  --line: #a9bdb5;
  --ink: #10201d;
  --muted: #3f5a53;
  --accent: #0f766e;
  --accent-dark: #0b5f57;
  --accent-soft: #d9f2ea;
  --danger: #b42318;
  --radius: 8px;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: "Segoe UI", "Noto Sans", sans-serif;
  color: var(--ink);
  background: linear-gradient(125deg, var(--bg-1) 35%, var(--bg-2) 65%);
  padding: 26px 24px 34px;
}

#salnSaveForm {
  margin-top: 22px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 24px;
  padding-bottom: 8px;
}

.header h1 {
  font-size: 3.5rem;
  font-weight: 950;
  margin: 0;
  color: var(--accent-dark);
  line-height: 1;
  display: inline-block;
  border-bottom: 6px solid var(--accent-dark);
}

.btn-dark {
  background-color: var(--accent-dark);
  color: #ffffff;
  font-weight: 800;
  font-size: 0.85rem;
  text-transform: uppercase;
  padding: 8px 16px;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  transition: background 0.2s ease;
}

.btn-dark:hover {
  background-color: #064e48;
}

.autosave-chip {
  height: fit-content;
  align-self: center;
  margin-right: 10px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.container {
  max-width: 1160px;
  margin: 0 auto;
  padding: 0 10px;
}

.actions-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid #bfd6ce;
  border-radius: 12px;
  padding: 14px 16px;
  box-shadow: 0 6px 18px rgba(15, 118, 110, 0.08);
}

.actions-header h1 {
  margin: 0;
  font-size: 1.65rem;
  font-weight: 900;
  letter-spacing: 0.2px;
  color: var(--accent-dark);
}

.top-actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 10px;
}

.section {
  border: 1px solid #bcd2cb;
  border-radius: 10px;
  padding: 18px;
  margin-bottom: 18px;
  background: var(--surface);
  box-shadow: 0 3px 10px rgba(15, 118, 110, 0.05);
}

.section h2 {
  font-size: 0.85rem;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--accent-dark);
  margin: 0 0 14px 0;
  padding-left: 10px;
  border-left: 4px solid var(--accent);
  line-height: 1.2;
}

.section h3 {
  font-size: 0.8rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--muted);
  margin: 16px 0 8px 0;
}

.row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

@media (max-width: 760px) {
  .row {
    grid-template-columns: 1fr;
  }
}

label {
  display: block;
  margin-bottom: 6px;
  font-weight: 900;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--ink);
}

input,
select {
  width: 100%;
  padding: 10px 12px;
  border: 1.5px solid var(--line);
  border-radius: 6px;
  background: #fff;
  font-size: 0.95rem;
  color: var(--ink);
}

input:focus,
select:focus {
  outline: none;
  border-color: var(--accent);
}

input[type="radio"],
input[type="checkbox"] {
  width: auto;
  flex-shrink: 0;
  accent-color: var(--accent);
  cursor: pointer;
  margin: 0;
}

.compliance-option {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 20px;
  padding: 10px;
  border-radius: var(--radius);
}

.compliance-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.compliance-input {
  width: 100%;
  max-width: 100%;
  display: block;
}

.compliance-option:hover {
  background-color: var(--accent-soft);
}

button {
  border: 1px solid var(--accent-dark);
  border-radius: var(--radius);
  padding: 8px 12px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  background: var(--accent);
  color: #fff;
}

button:hover {
  background: var(--accent-dark);
}

.field-group {
  border: 1.5px solid var(--line);
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 12px;
  background: #f9fcfb;
}

.list-item {
  border: 1px solid #c5dad3;
  background: #f9fdfb;
  border-radius: 6px;
  padding: 12px;
  margin-bottom: 10px;
}

.owner-scope-row {
  margin-top: 2px;
}

.owner-scope-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.35px;
  color: var(--muted);
  text-transform: uppercase;
  margin: 0;
  cursor: pointer;
}

.status {
  background: var(--accent-soft);
  border: 1px solid var(--accent);
  border-radius: 6px;
  color: var(--accent-dark);
  padding: 10px 12px;
  margin-bottom: 12px;
  font-weight: 600;
}

.error {
  color: var(--danger);
  font-size: 13px;
  margin-top: 4px;
  font-weight: 600;
}

.autosave-chip {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: var(--radius);
  font-size: 0.8rem;
  font-weight: 800;
  border: 1px solid var(--line);
  transition: all 0.2s ease;
}

.autosave-idle {
  background: #f1f7f5;
  color: var(--accent-dark);
  border: 1px solid var(--line);
}

.autosave-dirty {
  background: #ffe5e5;
  color: var(--danger);
  border: 1px solid var(--danger);
}

.inline {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  align-items: center;
}

.inline label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.9rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin: 0;
  cursor: pointer;
}

.actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 10px;
}

.secondary-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  padding: 7px 10px;
  font-size: 0.85rem;
  border-radius: var(--radius);
  background: #e8f3ef;
  color: var(--accent-dark);
  border: 1px solid var(--line);
  font-weight: 700;
}

.secondary-action:hover {
  background: #d7eee6;
}

.summary {
  font-weight: 900;
  margin-top: 8px;
  color: var(--accent-dark);
}

.total-assets-summary {
  border-top: 2px solid var(--accent);
  padding-top: 8px;
  margin-top: 12px;
}

.form-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 4px;
}

@media (max-width: 760px) {
  body {
    padding: 14px;
  }

  .actions-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .actions-header h1 {
    font-size: 1.35rem;
  }

  .top-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .header-actions {
    justify-content: flex-start;
    flex-wrap: wrap;
  }

  .compliance-option {
    flex-wrap: wrap;
  }

  .compliance-input {
    max-width: 100%;
    width: 100%;
  }
}
</style>
