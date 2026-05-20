<template>
  <div id="salnFormApp" class="container">
    <input type="hidden" id="saln-initial-data" :value="initialDataEncoded" />

    <div class="actions actions-header">
      <h1>SALN Form</h1>
      <div class="actions top-actions header-actions">
        <span id="autosaveStatus" class="autosave-chip autosave-idle">All changes saved</span>
        <button type="button" id="salnGeneratePdfButton" class="btn-dark">Generate PDF</button>
        <button type="button" class="btn-dark" @click="exportJson">Export JSON</button>
        <button type="button" id="salnImportButton" class="btn-dark">Import JSON</button>

        <form id="salnImportForm" enctype="multipart/form-data" hidden>
          <input
            type="file"
            id="salnImportInput"
            name="import_file"
            accept=".json,application/json"
          />
          <!-- ADD THIS RIGHT BEFORE </form> -->
        </form>
        <button type="button" @click="logout">Logout</button>
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
            />
            <label>Assumption of office as of</label>
          </div>
          <input
            class="compliance-input"
            data-compliance-target="assumption"
            type="date"
            name="assumption_date"
            v-model="form.assumption_date"
          />
        </div>

        <div class="compliance-option" data-compliance-option="annual">
          <div class="compliance-row">
            <input
              class="compliance-radio"
              type="radio"
              name="compliance_type"
              value="annual"
              v-model="form.compliance_type"
            />
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
          />
        </div>

        <div class="compliance-option" data-compliance-option="exit">
          <div class="compliance-row">
            <input
              class="compliance-radio"
              type="radio"
              name="compliance_type"
              value="exit"
              v-model="form.compliance_type"
            />
            <label>Exit as of</label>
          </div>
          <input
            class="compliance-input"
            data-compliance-target="exit"
            type="date"
            name="exit_date"
            v-model="form.exit_date"
          />
        </div>
      </div>

      <div class="section">
        <h2>Declarant</h2>

        <div class="two-column-grid">
          <div class="form-row">
            <label>Family Name</label>
            <input name="declarant[family_name]" v-model="form.declarant.family_name" />
          </div>

          <div class="form-row">
            <label>First Name</label>
            <input name="declarant[first_name]" v-model="form.declarant.first_name" />
          </div>

          <div class="form-row">
            <label>Middle Initial (Optional)</label>
            <input name="declarant[middle_initial]" v-model="form.declarant.middle_initial" />
          </div>

          <div class="form-row">
            <label>Position</label>
            <input name="declarant[position]" v-model="form.declarant.position" />
          </div>

          <div class="form-row">
            <label>Agency/Office</label>
            <input name="declarant[agency_office]" v-model="form.declarant.agency_office" />
          </div>

          <div class="form-row">
            <label>Office Address</label>
            <input name="declarant[office_address]" v-model="form.declarant.office_address" />
          </div>
        </div>
      </div>

      <div class="section">
        <h2>Spouse</h2>

        <div class="two-column-grid">
          <div class="form-row">
            <label>Family Name</label>
            <input name="spouse[family_name]" v-model="form.spouse.family_name" />
          </div>

          <div class="form-row">
            <label>First Name</label>
            <input name="spouse[first_name]" v-model="form.spouse.first_name" />
          </div>

          <div class="form-row">
            <label>Middle Initial (Optional)</label>
            <input name="spouse[middle_initial]" v-model="form.spouse.middle_initial" />
          </div>

          <div class="form-row">
            <label>Position</label>
            <input name="spouse[position]" v-model="form.spouse.position" />
          </div>

          <div class="form-row">
            <label>Agency/Office</label>
            <input name="spouse[agency_office]" v-model="form.spouse.agency_office" />
          </div>

          <div class="form-row">
            <label>Office Address</label>
            <input name="spouse[office_address]" v-model="form.spouse.office_address" />
          </div>
        </div>
      </div>

      <div class="section">
        <h2>Joint/Separate Filing</h2>
        <div class="inline">
          <label
            ><input type="radio" name="filing_type" value="joint" v-model="form.filing_type" />
            Joint Filing</label
          >
          <label
            ><input type="radio" name="filing_type" value="separate" v-model="form.filing_type" />
            Separate Filing</label
          >
          <label
            ><input
              type="radio"
              name="filing_type"
              value="not_applicable"
              v-model="form.filing_type"
            />
            Not Applicable</label
          >
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
        <div class="summary">
          Subtotal (Personal Properties): <span id="personalSubtotal">0.00</span>
        </div>

        <div class="summary total-assets-summary">
          Total Assets: <span id="totalAssets">0.00</span>
        </div>
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
      <div class="save-button-container">
        <button type="button" id="manualSaveBtn" class="save-btn">Save SALN</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue'
import salnApi from '../services/salnApi'
import authAPI from '@/services/api'
import { useRouter } from 'vue-router'

const router = useRouter()

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
})

const initialDataEncoded = computed(() => {
  const payload = {
    additionalSpouses: form.additional_spouses,
    children: form.children,
    realProperties: form.real_properties,
    personalProperties: form.personal_properties,
    businessInterests: form.business_interests,
    relativesInGovernmentService: form.relatives_in_government_service,
    liabilities: form.liabilities,
  }

  return btoa(JSON.stringify(payload))
})

async function loadFormData() {
  let response

  try {
    response = await salnApi.get('/saln')
  } catch (_error) {
    return
  }

  const payload = response.data || {}
  const data = payload.data || {}

  applyPayload(data)
}

function applyPayload(data) {
  const payload = data || {}

  form.compliance_type = payload.compliance_type || 'assumption'
  form.assumption_date = payload.assumption_date || ''
  form.annual_year = payload.annual_year || ''
  form.exit_date = payload.exit_date || ''
  form.declarant = { ...form.declarant, ...(payload.declarant || {}) }
  form.spouse = { ...form.spouse, ...(payload.spouse || {}) }
  form.filing_type = payload.filing_type || 'joint'
  form.additional_spouses = payload.additional_spouses || []
  form.children = payload.children || []
  form.real_properties = payload.real_properties || []
  form.personal_properties = payload.personal_properties || []
  form.business_interests = payload.business_interests || []
  form.relatives_in_government_service = payload.relatives_in_government_service || []
  form.liabilities = payload.liabilities || []
}

function parseFormFieldName(name) {
  return name.split(/\[|\]/).filter(Boolean)
}

function assignFormValue(target, name, value) {
  const keys = parseFormFieldName(name)

  if (!keys.length) {
    return
  }

  keys.reduce((cursor, key, index) => {
    const isLast = index === keys.length - 1

    if (isLast) {
      cursor[key] = value
      return cursor
    }

    const nextKey = keys[index + 1]

    if (!cursor[key]) {
      cursor[key] = /^\d+$/.test(nextKey) ? [] : {}
    }

    return cursor[key]
  }, target)
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
  }

  const saveForm = document.getElementById('salnSaveForm')

  if (saveForm) {
    const formData = new FormData(saveForm)

    for (const [name, value] of formData.entries()) {
      assignFormValue(payload, name, value)
    }
  }

  return payload
}

async function logout() {
  try {
    const response = await authAPI.post('/logout')

    if (response.status === 200) {
      localStorage.removeItem('access_token')
      router.replace({ path: '/' })
    }
  } catch (error) {
    console.error(error)
  }
}

function exportJson() {
  const blob = new Blob([JSON.stringify(currentFormPayload(), null, 2)], {
    type: 'application/json',
  })
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = 'saln-progress.json'
  document.body.appendChild(anchor)
  anchor.click()
  anchor.remove()
  URL.revokeObjectURL(url)
}

function initSalnForm() {
  const formContainer = document.getElementById('salnFormApp')

  if (!formContainer) {
    return
  }

  const saveForm = document.getElementById('salnSaveForm')
  const draftUrl = toSalnPath(saveForm?.dataset?.draftUrl || '/saln/draft')
  const pdfUrl = toSalnPath(saveForm?.dataset?.pdfUrl || '/saln/pdf')
  function toSalnPath(url) {
    if (!url) {
      return ''
    }

    try {
      const parsed = new URL(url)
      return parsed.pathname.replace(/^\/api/, '')
    } catch (_error) {
      return url.replace(/^\/api/, '')
    }
  }

  const initialDataInput = document.getElementById('saln-initial-data')
  const importButton = document.getElementById('salnImportButton')
  const importInput = document.getElementById('salnImportInput')
  const generatePdfButton = document.getElementById('salnGeneratePdfButton')
  const autosaveStatus = document.getElementById('autosaveStatus')

  const additionalSpousesContainer = document.getElementById('additionalSpousesContainer')
  const childrenContainer = document.getElementById('childrenContainer')
  const realPropertiesContainer = document.getElementById('realPropertiesContainer')
  const personalPropertiesContainer = document.getElementById('personalPropertiesContainer')
  const businessInterestsContainer = document.getElementById('businessInterestsContainer')
  const relativesInGovernmentServiceContainer = document.getElementById(
    'relativesInGovernmentServiceContainer',
  )
  const liabilitiesContainer = document.getElementById('liabilitiesContainer')

  const counters = {
    spouse: 0,
    child: 0,
    real: 0,
    personal: 0,
    business: 0,
    relative: 0,
    liability: 0,
  }

  function loadInitialData() {
    const fallback = {
      additionalSpouses: [],
      children: [],
      realProperties: [],
      personalProperties: [],
      businessInterests: [],
      relativesInGovernmentService: [],
      liabilities: [],
    }

    if (!initialDataInput || !initialDataInput.value) {
      return fallback
    }

    try {
      return {
        ...fallback,
        ...JSON.parse(atob(initialDataInput.value)),
      }
    } catch (_error) {
      return fallback
    }
  }

  const initialData = loadInitialData()

  function resetDynamicRows() {
    if (additionalSpousesContainer) {
      additionalSpousesContainer.innerHTML = ''
    }
    if (childrenContainer) {
      childrenContainer.innerHTML = ''
    }
    if (realPropertiesContainer) {
      realPropertiesContainer.innerHTML = ''
    }
    if (personalPropertiesContainer) {
      personalPropertiesContainer.innerHTML = ''
    }
    if (businessInterestsContainer) {
      businessInterestsContainer.innerHTML = ''
    }
    if (relativesInGovernmentServiceContainer) {
      relativesInGovernmentServiceContainer.innerHTML = ''
    }
    if (liabilitiesContainer) {
      liabilitiesContainer.innerHTML = ''
    }

    counters.spouse = 0
    counters.child = 0
    counters.real = 0
    counters.personal = 0
    counters.business = 0
    counters.relative = 0
    counters.liability = 0
  }

  function rebuildRowsFromForm() {
    ;(form.additional_spouses || []).forEach(function (row) {
      addAdditionalSpouseRow(row)
    })
    ;(form.children || []).forEach(function (row) {
      addChildRow(row)
    })
    ;(form.real_properties || []).forEach(function (row) {
      addRealPropertyRow(row)
    })
    ;(form.personal_properties || []).forEach(function (row) {
      addPersonalPropertyRow(row)
    })
    ;(form.business_interests || []).forEach(function (row) {
      addBusinessInterestRow(row)
    })
    ;(form.relatives_in_government_service || []).forEach(function (row) {
      addRelativeInGovernmentServiceRow(row)
    })
    ;(form.liabilities || []).forEach(function (row) {
      addLiabilityRow(row)
    })
  }

  function toNumber(value) {
    const parsed = parseFloat(value)
    return Number.isFinite(parsed) ? parsed : 0
  }

  function formatMoney(value) {
    return value.toFixed(2)
  }

  function createRemoveButton(wrapper) {
    const button = document.createElement('button')
    button.type = 'button'
    button.textContent = 'Remove'
    button.addEventListener('click', function () {
      wrapper.remove()
      updateTotals()
      scheduleDraftSave()
    })
    return button
  }

  function ownerScopeField(name, selectedValue) {
    const spouseChildrenOwned = selectedValue === 'spouse_children'

    return `
            <div class="owner-scope-row">
                <input type="hidden" name="${name}" value="declarant">
                <label class="owner-scope-checkbox">
                    <input type="checkbox" name="${name}" value="spouse_children" ${spouseChildrenOwned ? 'checked' : ''}>
                    Owned by spouse / children
                </label>
            </div>
        `
  }

  function calculateAge(dateStr) {
    if (!dateStr) {
      return ''
    }

    const birthDate = new Date(dateStr)
    if (Number.isNaN(birthDate.getTime())) {
      return ''
    }

    const now = new Date()
    let age = now.getFullYear() - birthDate.getFullYear()
    const monthDiff = now.getMonth() - birthDate.getMonth()
    const dayDiff = now.getDate() - birthDate.getDate()

    if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
      age -= 1
    }

    return age >= 0 ? String(age) : ''
  }

  function addAdditionalSpouseRow(data = {}) {
    const index = counters.spouse++
    const wrapper = document.createElement('div')
    wrapper.className = 'list-item'
    wrapper.innerHTML = `
            <div class="form-row">
                <label>Name</label>
                <input name="additional_spouses[${index}][name]" value="${data.name || ''}">
            </div>
        `
    wrapper.appendChild(createRemoveButton(wrapper))
    additionalSpousesContainer.appendChild(wrapper)
  }

  function addChildRow(data = {}) {
    const index = counters.child++
    const wrapper = document.createElement('div')
    wrapper.className = 'list-item'
    wrapper.innerHTML = `
            <div class="two-column-grid dynamic-grid">
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
        `
    wrapper.appendChild(createRemoveButton(wrapper))
    childrenContainer.appendChild(wrapper)

    const dobInput = wrapper.querySelector('.child-dob')
    const ageTarget = wrapper.querySelector('.child-age')
    ageTarget.textContent = calculateAge(dobInput.value)

    dobInput.addEventListener('input', function () {
      ageTarget.textContent = calculateAge(dobInput.value)
    })
  }

  function addRealPropertyRow(data = {}) {
    const index = counters.real++
    const wrapper = document.createElement('div')
    wrapper.className = 'list-item real-property-row'
    wrapper.innerHTML = `
            <div class="two-column-grid dynamic-grid">
                <div class="form-row"><label>Description</label><input name="real_properties[${index}][description]" value="${data.description || ''}"></div>
                <div class="form-row"><label>Kind</label><input name="real_properties[${index}][kind]" value="${data.kind || ''}"></div>
                <div class="form-row"><label>Exact Location</label><input name="real_properties[${index}][exact_location]" value="${data.exact_location || ''}"></div>
                <div class="form-row"><label>Assessed Value</label><input type="number" step="0.01" min="0" name="real_properties[${index}][assessed_value]" value="${data.assessed_value || ''}"></div>
                <div class="form-row"><label>Current Fair Market Value</label><input type="number" step="0.01" min="0" name="real_properties[${index}][current_fair_market_value]" value="${data.current_fair_market_value || ''}"></div>
                <div class="form-row"><label>Year of Acquisition</label><input type="text" name="real_properties[${index}][year_of_acquisition]" value="${data.year_of_acquisition || ''}"></div>
                <div class="form-row"><label>Mode of Acquisition</label><input name="real_properties[${index}][mode_of_acquisition]" value="${data.mode_of_acquisition || ''}"></div>
                <div class="form-row"><label>Acquisition Cost</label><input type="number" step="0.01" min="0" class="real-cost" name="real_properties[${index}][acquisition_cost]" value="${data.acquisition_cost || ''}"></div>
                
            </div>
        `
    const owner = document.createElement('div')
    owner.innerHTML = ownerScopeField(`real_properties[${index}][owner_scope]`, data.owner_scope)

    wrapper.appendChild(owner)
    wrapper.appendChild(createRemoveButton(wrapper))
    realPropertiesContainer.appendChild(wrapper)
  }

  function addPersonalPropertyRow(data = {}) {
    const index = counters.personal++
    const wrapper = document.createElement('div')
    wrapper.className = 'list-item personal-property-row'
    wrapper.innerHTML = `
            <div class="two-column-grid dynamic-grid">
                <div class="form-row"><label>Description</label><input name="personal_properties[${index}][description]" value="${data.description || ''}"></div>
                <div class="form-row"><label>Acquisition Year</label><input type="text" name="personal_properties[${index}][acquisition_year]" value="${data.acquisition_year || ''}"></div>
                <div class="form-row"><label>Acquisition Cost / Amount</label><input type="number" step="0.01" min="0" class="personal-cost" name="personal_properties[${index}][acquisition_cost_amount]" value="${data.acquisition_cost_amount || ''}"></div>
                ${ownerScopeField(`personal_properties[${index}][owner_scope]`, data.owner_scope)}
            </div>
        `
    wrapper.appendChild(createRemoveButton(wrapper))
    personalPropertiesContainer.appendChild(wrapper)
  }

  function addBusinessInterestRow(data = {}) {
    const index = counters.business++
    const wrapper = document.createElement('div')
    wrapper.className = 'list-item business-interest-row'
    wrapper.innerHTML = `
            <div class="two-column-grid dynamic-grid">
                <div class="form-row"><label>Name of Entity / Business Enterprise</label><input name="business_interests[${index}][name_of_entity_or_business_enterprise]" value="${data.name_of_entity_or_business_enterprise || ''}"></div>
                <div class="form-row"><label>Business Address</label><input name="business_interests[${index}][business_address]" value="${data.business_address || ''}"></div>
                <div class="form-row"><label>Nature of Business Interest / Financial Connection</label><input name="business_interests[${index}][nature_of_business_interest_or_financial_connection]" value="${data.nature_of_business_interest_or_financial_connection || ''}"></div>
                <div class="form-row"><label>Date of Acquisition of Interest or Connection</label><input type="date" name="business_interests[${index}][date_of_acquisition]" value="${data.date_of_acquisition || ''}"></div>
                ${ownerScopeField(`business_interests[${index}][owner_scope]`, data.owner_scope)}
            </div>
        `
    wrapper.appendChild(createRemoveButton(wrapper))
    businessInterestsContainer.appendChild(wrapper)
  }

  function addRelativeInGovernmentServiceRow(data = {}) {
    const index = counters.relative++
    const wrapper = document.createElement('div')
    wrapper.className = 'list-item relative-government-row'
    wrapper.innerHTML = `
            <div class="two-column-grid dynamic-grid">
                <div class="form-row"><label>Name of Relative</label><input name="relatives_in_government_service[${index}][name_of_relative]" value="${data.name_of_relative || ''}"></div>
                <div class="form-row"><label>Relationship</label><input name="relatives_in_government_service[${index}][relationship]" value="${data.relationship || ''}"></div>
                <div class="form-row"><label>Position</label><input name="relatives_in_government_service[${index}][position]" value="${data.position || ''}"></div>
                <div class="form-row"><label>Name of Agency / Office and Address</label><input name="relatives_in_government_service[${index}][name_of_agency_office_and_address]" value="${data.name_of_agency_office_and_address || ''}"></div>
            </div>
        `
    wrapper.appendChild(createRemoveButton(wrapper))
    relativesInGovernmentServiceContainer.appendChild(wrapper)
  }

  function addLiabilityRow(data = {}) {
    const index = counters.liability++
    const wrapper = document.createElement('div')
    wrapper.className = 'list-item liability-row'
    wrapper.innerHTML = `
            <div class="two-column-grid dynamic-grid">
                <div class="form-row"><label>Nature</label><input name="liabilities[${index}][nature]" value="${data.nature || ''}"></div>
                <div class="form-row"><label>Name of Creditor</label><input name="liabilities[${index}][name_of_creditor]" value="${data.name_of_creditor || ''}"></div>
                <div class="form-row"><label>Outstanding Balance</label><input type="number" step="0.01" min="0" class="liability-balance" name="liabilities[${index}][outstanding_balance]" value="${data.outstanding_balance || ''}"></div>
                ${ownerScopeField(`liabilities[${index}][owner_scope]`, data.owner_scope)}
            </div>
        `
    wrapper.appendChild(createRemoveButton(wrapper))
    liabilitiesContainer.appendChild(wrapper)
  }

  function updateTotals() {
    let realSubtotal = 0
    let personalSubtotal = 0
    let liabilitiesTotal = 0

    document.querySelectorAll('.real-cost').forEach(function (input) {
      realSubtotal += toNumber(input.value)
    })

    document.querySelectorAll('.personal-cost').forEach(function (input) {
      personalSubtotal += toNumber(input.value)
    })

    document.querySelectorAll('.liability-balance').forEach(function (input) {
      liabilitiesTotal += toNumber(input.value)
    })

    const totalAssets = realSubtotal + personalSubtotal
    const netWorth = totalAssets - liabilitiesTotal

    document.getElementById('realSubtotal').textContent = formatMoney(realSubtotal)
    document.getElementById('personalSubtotal').textContent = formatMoney(personalSubtotal)
    document.getElementById('totalAssets').textContent = formatMoney(totalAssets)
    document.getElementById('totalLiabilities').textContent = formatMoney(liabilitiesTotal)
    document.getElementById('netWorth').textContent = formatMoney(netWorth)
  }

  function updateComplianceInputs() {
    const selectedRadio = document.querySelector('.compliance-radio:checked')
    const selectedValue = selectedRadio ? selectedRadio.value : ''

    document.querySelectorAll('.compliance-option').forEach(function (option) {
      const optionValue = option.getAttribute('data-compliance-option')
      const input = option.querySelector('.compliance-input')

      if (!input) {
        return
      }

      const isSelected = optionValue === selectedValue
      input.disabled = !isSelected
      input.required = isSelected
    })
  }

  function createDraftRequestBody() {
    return new FormData(saveForm)
  }

  function createDraftSignature() {
    if (!saveForm) {
      return ''
    }

    return JSON.stringify(Array.from(new FormData(saveForm).entries()))
  }

  let draftTimer = null
  let draftInFlight = null
  let draftRequestSeq = 0
  let lastSavedDraftSignature = ''

  function setAutosaveState(state, message) {
    if (!autosaveStatus) {
      return
    }

    autosaveStatus.classList.remove(
      'autosave-idle',
      'autosave-dirty',
      'autosave-saving',
      'autosave-saved',
      'autosave-error',
    )

    autosaveStatus.classList.add(`autosave-${state}`)
    autosaveStatus.textContent = message
  }

  function savedAtMessage() {
    const now = new Date()
    const time = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    return `Saved at ${time}`
  }

  function persistDraft() {
    if (!saveForm) {
      return Promise.resolve()
    }

    if (draftInFlight) {
      const activeRequest = draftInFlight

      return activeRequest.then(function () {
        return persistDraft()
      })
    }

    const draftSignature = createDraftSignature()

    if (draftSignature === lastSavedDraftSignature) {
      return Promise.resolve(true)
    }

    const requestSeq = ++draftRequestSeq
    setAutosaveState('saving', 'Saving...')

    const body = createDraftRequestBody()
    const request = salnApi
      .post(draftUrl, body, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
      .then(function () {
        if (requestSeq === draftRequestSeq) {
          lastSavedDraftSignature = draftSignature
          setAutosaveState('saved', savedAtMessage())
        }

        return true
      })
      .catch(function () {
        if (requestSeq === draftRequestSeq) {
          setAutosaveState('error', 'Autosave failed. Continuing to retry.')
        }

        return null
      })
      .finally(function () {
        if (draftInFlight === request) {
          draftInFlight = null
        }
      })

    draftInFlight = request
    return request
  }

  function scheduleDraftSave() {
    const draftSignature = createDraftSignature()

    if (draftSignature === lastSavedDraftSignature) {
      if (draftTimer) {
        window.clearTimeout(draftTimer)
        draftTimer = null
      }

      setAutosaveState('saved', 'All changes saved')
      return
    }

    if (draftTimer) {
      window.clearTimeout(draftTimer)
    }

    setAutosaveState('dirty', 'Unsaved changes')

    draftTimer = window.setTimeout(function () {
      draftTimer = null
      persistDraft()
    }, 500)
  }

  function triggerDirectDownload(downloadUrl) {
    const anchor = document.createElement('a')
    anchor.href = downloadUrl
    anchor.rel = 'noopener'
    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
  }

  async function extractErrorMessage(error) {
    const payload = error?.response?.data

    if (payload?.message) {
      return payload.message
    }

    if (payload?.errors && typeof payload.errors === 'object') {
      const firstErrorKey = Object.keys(payload.errors)[0]
      if (
        firstErrorKey &&
        Array.isArray(payload.errors[firstErrorKey]) &&
        payload.errors[firstErrorKey][0]
      ) {
        return payload.errors[firstErrorKey][0]
      }
    }

    return 'PDF generation failed. Please try again.'
  }

  async function generatePdf() {
    if (generatePdfButton) {
      generatePdfButton.disabled = true
    }

    try {
      if (draftTimer) {
        window.clearTimeout(draftTimer)
        draftTimer = null
      }

      if (draftInFlight) {
        await draftInFlight
      }

      const saved = await persistDraft()
      if (!saved) {
        throw new Error('Autosave failed. Please try again before generating PDF.')
      }

      setAutosaveState('saving', 'Generating PDF...')

      let payload

      try {
        const response = await salnApi.post(pdfUrl, createDraftRequestBody(), {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        payload = response.data
      } catch (error) {
        throw new Error(await extractErrorMessage(error))
      }

      if (!payload.download_url) {
        throw new Error('PDF download URL missing.')
      }

      triggerDirectDownload(payload.download_url)
      setAutosaveState('saved', 'PDF generated successfully.')
    } catch (error) {
      setAutosaveState(
        'error',
        error instanceof Error ? error.message : 'PDF generation failed. Please try again.',
      )
    } finally {
      if (generatePdfButton) {
        generatePdfButton.disabled = false
      }
    }
  }
  document.getElementById('manualSaveBtn').addEventListener('click', function () {
    void persistDraft()
  })
  document.getElementById('addSpouseBtn').addEventListener('click', function () {
    addAdditionalSpouseRow()
  })
  document.getElementById('addChildBtn').addEventListener('click', function () {
    addChildRow()
  })
  document.getElementById('addRealPropertyBtn').addEventListener('click', function () {
    addRealPropertyRow()
    updateTotals()
    scheduleDraftSave()
  })
  document.getElementById('addPersonalPropertyBtn').addEventListener('click', function () {
    addPersonalPropertyRow()
    updateTotals()
    scheduleDraftSave()
  })
  document.getElementById('addBusinessInterestBtn').addEventListener('click', function () {
    addBusinessInterestRow()
    scheduleDraftSave()
  })
  document
    .getElementById('addRelativeInGovernmentServiceBtn')
    .addEventListener('click', function () {
      addRelativeInGovernmentServiceRow()
      scheduleDraftSave()
    })
  document.getElementById('addLiabilityBtn').addEventListener('click', function () {
    addLiabilityRow()
    updateTotals()
    scheduleDraftSave()
  })

  document.addEventListener('input', function (event) {
    if (
      event.target.classList.contains('real-cost') ||
      event.target.classList.contains('personal-cost') ||
      event.target.classList.contains('liability-balance')
    ) {
      updateTotals()
    }

    if (event.target.closest('#salnSaveForm')) {
      scheduleDraftSave()
    }
  })

  document.addEventListener('change', function (event) {
    if (event.target.closest('#salnSaveForm')) {
      scheduleDraftSave()
    }
  })

  document.querySelectorAll('.compliance-radio').forEach(function (radio) {
    radio.addEventListener('change', updateComplianceInputs)
  })

  if (importButton && importInput) {
    importButton.addEventListener('click', function () {
      importInput.click()
    })

    importInput.addEventListener('change', function () {
      const file = importInput.files && importInput.files[0]

      if (!file) {
        return
      }

      const payload = new FormData()
      payload.append('import_file', file)

      setAutosaveState('saving', 'Importing JSON...')

      salnApi
        .post('/saln/import', payload, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        .then((response) => response.data)
        .then((responsePayload) => {
          const data = responsePayload?.data || {}
          applyPayload(data)
          resetDynamicRows()
          rebuildRowsFromForm()
          updateComplianceInputs()
          updateTotals()
          lastSavedDraftSignature = createDraftSignature()
          setAutosaveState('saved', 'SALN JSON imported successfully.')
        })
        .catch((error) => {
          const message =
            error?.response?.data?.message || 'Import failed. Please check the file and try again.'
          setAutosaveState('error', message)
        })
        .finally(() => {
          importInput.value = ''
        })
    })
  }

  if (generatePdfButton) {
    generatePdfButton.addEventListener('click', function () {
      void generatePdf()
    })
  }

  ;(initialData.additionalSpouses || []).forEach(function (row) {
    addAdditionalSpouseRow(row)
  })
  ;(initialData.children || []).forEach(function (row) {
    addChildRow(row)
  })
  ;(initialData.realProperties || []).forEach(function (row) {
    addRealPropertyRow(row)
  })
  ;(initialData.personalProperties || []).forEach(function (row) {
    addPersonalPropertyRow(row)
  })
  ;(initialData.businessInterests || []).forEach(function (row) {
    addBusinessInterestRow(row)
  })
  ;(initialData.relativesInGovernmentService || []).forEach(function (row) {
    addRelativeInGovernmentServiceRow(row)
  })
  ;(initialData.liabilities || []).forEach(function (row) {
    addLiabilityRow(row)
  })

  updateComplianceInputs()
  updateTotals()
  lastSavedDraftSignature = createDraftSignature()

  document.addEventListener('pagehide', function () {
    if (draftTimer) {
      window.clearTimeout(draftTimer)
      draftTimer = null
    }

    if (draftInFlight) {
      return
    }

    void persistDraft()
  })
}

onMounted(async () => {
  await loadFormData()
  initSalnForm()
})
</script>
# SALN Form Vue File (Timeless Professional Redesign)

<style>
:root {
  --bg-primary: #f4f6f8;
  --bg-secondary: #eef1f4;

  --surface: #ffffff;
  --surface-light: #fafbfc;

  --border: #d7dde5;
  --border-strong: #b8c2cc;

  --text-primary: #1f2937;
  --text-secondary: #4b5563;
  --text-muted: #6b7280;

  --accent: #1d4ed8;
  --accent-hover: #1e40af;
  --accent-strong: #1e3a8a;

  --button-dark: #1f2937;
  --button-dark-text: #ffffff;

  --danger: #dc2626;
  --warning: #d97706;

  --shadow: 0 2px 10px rgba(15, 23, 42, 0.06);

  --radius-lg: 14px;
  --radius-md: 10px;
  --radius-sm: 8px;
}

* {
  box-sizing: border-box;
}

html,
body,
#app {
  margin: 0;
  min-height: 100%;
  width: 100%;

  font-family: Inter, 'Segoe UI', sans-serif;

  background: var(--bg-primary);
  color: var(--text-primary);
}

body {
  padding: 0;
}

#salnFormApp {
  width: 100%;
  min-height: 100vh;
  padding: 24px;
}

.container {
  width: 100%;
  max-width: 100%;
  margin: 0 auto;
}

.actions-header {
  width: 100%;

  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;

  padding: 22px 28px;

  border-radius: var(--radius-lg);

  background: white;
  border: 1px solid var(--border);

  box-shadow: var(--shadow);

  margin-bottom: 24px;
}

.actions-header h1 {
  margin: 0;

  font-size: 2rem;
  font-weight: 700;

  letter-spacing: -0.5px;

  color: var(--text-primary);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

button,
.btn-dark {
  border: none;
  outline: none;
  cursor: pointer;

  border-radius: 10px;

  padding: 11px 18px;

  font-size: 0.9rem;
  font-weight: 600;

  transition:
    background 0.15s ease,
    transform 0.15s ease;

  background: var(--accent);
  color: white;
}

button:hover,
.btn-dark:hover {
  background: var(--accent-hover);
  transform: translateY(-1px);
}

#addSpouseBtn,
#addChildBtn,
#addRealPropertyBtn,
#addPersonalPropertyBtn,
#addBusinessInterestBtn,
#addRelativeInGovernmentServiceBtn,
#addLiabilityBtn {
  margin-top: 12px;

  background: #f3f4f6;
  color: var(--text-primary);

  border: 1px solid var(--border);
}

#addSpouseBtn:hover,
#addChildBtn:hover,
#addRealPropertyBtn:hover,
#addPersonalPropertyBtn:hover,
#addBusinessInterestBtn:hover,
#addRelativeInGovernmentServiceBtn:hover,
#addLiabilityBtn:hover {
  background: #e5e7eb;
}

.autosave-chip {
  padding: 8px 12px;

  border-radius: 999px;

  font-size: 0.8rem;
  font-weight: 600;

  border: 1px solid #dbeafe;

  background: #eff6ff;

  color: #1e40af;
}

#salnSaveForm {
  width: 100%;
}

.section {
  width: 100%;

  background: white;
  border: 1px solid var(--border);

  border-radius: var(--radius-lg);

  padding: 28px;
  margin-bottom: 22px;

  box-shadow: var(--shadow);
}

.section:hover {
  border-color: var(--border);
}

.section h2 {
  margin: 0 0 22px;

  font-size: 1rem;
  font-weight: 700;

  text-transform: uppercase;
  letter-spacing: 0.8px;

  color: var(--text-primary);

  padding-bottom: 12px;
  border-bottom: 1px solid var(--border);
}

.section h2::before {
  display: none;
}

.section h3 {
  margin-top: 26px;
  margin-bottom: 14px;

  color: var(--text-secondary);

  font-size: 0.95rem;
  font-weight: 700;

  text-transform: uppercase;
  letter-spacing: 0.6px;
}

.row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 18px;
}

.form-row {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

label {
  font-size: 0.78rem;
  font-weight: 700;

  text-transform: uppercase;
  letter-spacing: 0.7px;

  color: var(--text-secondary);
}

input,
select,
textarea {
  width: 100%;

  border: 1px solid var(--border);
  border-radius: var(--radius-md);

  padding: 12px 14px;

  font-size: 0.95rem;

  background: white;
  color: var(--text-primary);

  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease;
}

input::placeholder,
textarea::placeholder {
  color: var(--text-muted);
}

input:focus,
select:focus,
textarea:focus {
  outline: none;

  border-color: var(--accent);

  box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
}

input[type='radio'],
input[type='checkbox'] {
  width: auto;
  accent-color: var(--accent);
}

.inline {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
}

.inline label {
  display: flex;
  align-items: center;
  gap: 8px;

  background: #f8fafc;

  border: 1px solid var(--border);

  padding: 10px 14px;

  border-radius: 999px;

  color: var(--text-primary);

  text-transform: none;
}

.compliance-option {
  background: #fafbfc;

  border: 1px solid var(--border);

  border-radius: var(--radius-md);

  padding: 18px;

  margin-bottom: 16px;

  transition: border-color 0.15s ease;
}

.compliance-option:hover {
  border-color: var(--border-strong);
}

.compliance-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.compliance-input {
  width: 100%;
}

.list-item {
  background: #fbfcfd;

  border: 1px solid var(--border);

  border-radius: 14px;

  padding: 20px;

  margin-bottom: 16px;
}

.owner-scope-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 8px;

  background: #f8fafc;

  border: 1px solid var(--border);

  padding: 10px 14px;

  border-radius: 999px;

  color: var(--text-secondary);

  font-size: 0.8rem;
  font-weight: 600;

  margin-top: 10px;
}

.summary {
  margin-top: 16px;

  padding: 14px 18px;

  border-radius: 10px;

  background: #f8fafc;

  border: 1px solid var(--border);

  color: var(--text-primary);

  font-size: 0.95rem;
  font-weight: 600;
}

.total-assets-summary {
  border-left: 4px solid var(--accent);
}

.list-item button {
  margin-top: 14px;

  background: #dc2626;
  color: white;
}

.list-item button:hover {
  background: #b91c1c;
}

.status {
  padding: 14px;

  border-radius: 10px;

  background: #eff6ff;

  border: 1px solid #bfdbfe;

  color: #1e3a8a;

  font-weight: 600;
}

.error {
  color: var(--danger);
  font-size: 0.85rem;
  font-weight: 600;
}

.save-button-container {
  width: 100%;

  display: flex;
  justify-content: center;

  margin-top: 32px;
  margin-bottom: 50px;
}

.save-btn {
  min-width: 240px;

  padding: 16px 28px;

  font-size: 1rem;
  font-weight: 700;

  border-radius: 12px;

  background: var(--accent);
  color: white;

  box-shadow: 0 4px 10px rgba(29, 78, 216, 0.15);
}

.save-btn:hover {
  background: var(--accent-hover);
}

.two-column-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
  width: 100%;
}

.two-column-grid .form-row {
  width: 100%;
}

.dynamic-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

::-webkit-scrollbar {
  width: 10px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 999px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
}

@media (max-width: 900px) {
  #salnFormApp {
    padding: 14px;
  }

  .actions-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-actions {
    width: 100%;
  }

  .header-actions button,
  .btn-dark {
    width: 100%;
  }

  .section {
    padding: 20px;
  }

  .row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .two-column-grid,
  .dynamic-grid {
    grid-template-columns: 1fr;
  }
}
</style>
```
