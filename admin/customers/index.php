<?php  include __DIR__.'/../layout/header.php'; ?>
<?php  include __DIR__.'/../layout/sidebar.php'; ?>

<!-- MAIN CONTENT -->
<div class="main">

  <!-- TOP BAR -->
  <div class="topbar">
    <h2>Customer Dashboard</h2>
    <div class="admin-info">
      Admin User
    </div>
  </div>

  <!-- STATS -->
  <div class="cards">
    <div class="card">
      <h3>Total Customers</h3>
      <p id="totalCustomers">0</p>
    </div>
  </div>

  <!-- CUSTOMER TABLE -->
  <div class="container">

    <h2>Customer List</h2>

    <div class="search-box">
      <input type="text" id="search" placeholder="Search customer...">
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Customer ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Created At</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="customerTable">
          <tr>
            <td colspan="6" class="loading">Loading data...</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div id="pagination" style="margin-top:15px; text-align:center;"></div>
    
  </div>

</div>
<style>
  .custom-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.page-btn {
    min-width: 40px;
    height: 40px;
    padding: 0 14px;
    border: 1px solid #dcdfe4;
    background: #fff;
    color: #333;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.25s ease;
}

.page-btn:hover:not(:disabled) {
    background: #f4f6f9;
    border-color: #007bff;
    color: #007bff;
}

.page-btn.active {
    background: #007bff;
    border-color: #007bff;
    color: #fff;
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.nav-btn {
    padding: 0 16px;
    font-weight: 600;
}
</style>

<script>
  $(document).ready(function () {
    function showCustomAlert(card, checkout = null) {
      let isVisible = false;
      /* ================= Checkout Section ================= */
      let checkoutBox = null;
      let checkoutObj = null;

      try {
        if (checkout && checkout !== 'undefined') {
          checkoutObj = typeof checkout === 'string'
            ? JSON.parse(decodeURIComponent(checkout))
            : checkout;
        }
      } catch (e) {
        console.error('Checkout parse failed', e);
      }

      const customerData = checkoutObj?.['weedex.customer'];
      const checkoutData = checkoutObj?.['weedex.checkout'];

      /* ================= Overlay ================= */
      const overlay = document.createElement('div');
      Object.assign(overlay.style, {
        position: 'fixed',
        inset: 0,
        background: 'rgba(15,23,42,.85)',
        backdropFilter: 'blur(4px)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        zIndex: 9999,
        padding: '16px'
      });

      overlay.onclick = (e) => {
        if (e.target === overlay) {
          document.body.removeChild(overlay);
        }
      };

      /* ================= Modal ================= */
      const box = document.createElement('div');
      Object.assign(box.style, {
        background: '#fff',
        borderRadius: '12px',
        width: '480px',
        maxWidth: '95vw',
        maxHeight: '80vh',
        overflow: 'auto',
        padding: '12px',
        fontFamily: 'Inter, system-ui, Arial, sans-serif',
        boxShadow: '0 25px 55px rgba(0,0,0,.4)'
      });

      box.animate(
        [
          { transform: 'scale(0.9) translateY(20px)', opacity: 0 },
          { transform: 'scale(1) translateY(0)', opacity: 1 }
        ],
        { duration: 300, easing: 'cubic-bezier(0.34, 1.56, 0.64, 1)' }
      );

      /* ================= Header ================= */
      const header = document.createElement('div');
      Object.assign(header.style, {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '10px',
        paddingBottom: '8px',
        borderBottom: '1px solid #f1f5f9'
      });

      console.log(checkoutData);
      const title = document.createElement('h2');
      title.textContent = `💳 Payment & Cart Details (${(checkoutData.priceMode ?? "BASE").toUpperCase()})`;
      Object.assign(title.style, {
        fontSize: '13px',
        fontWeight: '700',
        color: '#0f172a',
        margin: '0'
      });

      const closeIcon = document.createElement('button');
      closeIcon.innerHTML = '✕';
      Object.assign(closeIcon.style, {
        background: '#f1f5f9',
        border: 'none',
        borderRadius: '50%',
        width: '24px',
        height: '24px',
        fontSize: '12px',
        cursor: 'pointer',
        color: '#64748b',
        transition: 'all 0.2s'
      });
      closeIcon.onmouseover = () => {
        closeIcon.style.background = '#e2e8f0';
        closeIcon.style.color = '#0f172a';
      };
      closeIcon.onmouseout = () => {
        closeIcon.style.background = '#f1f5f9';
        closeIcon.style.color = '#64748b';
      };
      closeIcon.onclick = () => document.body.removeChild(overlay);

      header.append(title, closeIcon);

      /* ================= Content Row ================= */
      const content = document.createElement('div');
      Object.assign(content.style, {
        display: 'grid',
        gridTemplateColumns: '1fr 1fr',
        gap: '10px',
        marginBottom: '10px',
        alignItems: 'start'
      });

      /* ================= Card Section ================= */
      const cardUI = document.createElement('div');
      Object.assign(cardUI.style, {
        background: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
        color: '#fff',
        borderRadius: '10px',
        padding: '12px',
        height: '140px',
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'space-between',
        position: 'relative',
        overflow: 'hidden'
      });

      // Decorative circles
      const circle1 = document.createElement('div');
      Object.assign(circle1.style, {
        position: 'absolute',
        width: '90px',
        height: '90px',
        borderRadius: '50%',
        background: 'rgba(255,255,255,0.05)',
        top: '-30px',
        right: '-30px'
      });

      const circle2 = document.createElement('div');
      Object.assign(circle2.style, {
        position: 'absolute',
        width: '60px',
        height: '60px',
        borderRadius: '50%',
        background: 'rgba(255,255,255,0.03)',
        bottom: '-20px',
        left: '-20px'
      });

      cardUI.append(circle1, circle2);

      const brand = document.createElement('div');
      brand.textContent = card.brand?.toUpperCase() || 'CARD';
      Object.assign(brand.style, {
        textAlign: 'right',
        fontWeight: '700',
        fontSize: '11px',
        letterSpacing: '1px',
        position: 'relative',
        zIndex: '1'
      });

      const middle = document.createElement('div');
      middle.style.position = 'relative';
      middle.style.zIndex = '1';

      const chip = document.createElement('div');
      Object.assign(chip.style, {
        width: '30px',
        height: '22px',
        borderRadius: '4px',
        background: 'linear-gradient(135deg, #fde047, #facc15, #ca8a04)',
        marginBottom: '8px',
        boxShadow: '0 4px 10px rgba(0,0,0,0.2)'
      });

      const number = document.createElement('div');
      Object.assign(number.style, {
        fontSize: '12px',
        letterSpacing: '2px',
        fontFamily: 'monospace'
      });

      middle.append(chip, number);

      const footer = document.createElement('div');
      Object.assign(footer.style, {
        display: 'flex',
        justifyContent: 'space-between',
        fontSize: '10px',
        position: 'relative',
        zIndex: '1'
      });

      const expiry = document.createElement('div');
      const cvc = document.createElement('div');

      function updateView() {
        number.textContent = isVisible
          ? card.number.match(/.{1,4}/g).join(' ')
          : `•••• •••• •••• ${card.number.slice(-4)}`;

        expiry.innerHTML = `<small style="opacity:0.7;font-size:9px">VALID THRU</small><br><span style="font-weight:600">${card.expiry.month}/${card.expiry.year}</span>`;
        cvc.innerHTML = `<small style="opacity:0.7;font-size:9px">CVC</small><br><span style="font-weight:600">${isVisible ? card.cvc : '•••'}</span>`;
      }

      updateView();
      footer.append(expiry, cvc);
      cardUI.append(brand, middle, footer);

      if (customerData || checkoutData) {
        checkoutBox = document.createElement('div');
        Object.assign(checkoutBox.style, {
          display: 'flex',
          flexDirection: 'column',
          gap: '6px'
        });

        let innerHTML = `
          <div style="font-weight:700;font-size:12px;color:#0f172a;display:flex;align-items:center;gap:4px">
            🛒 Cart Summary
          </div>
        `;

        if (customerData?.address) {
          innerHTML += `
            <div>
              <div style="
                display:flex;
                align-items:center;
                gap:4px;
                margin-bottom:3px;
                font-size:9px;
                font-weight:600;
                color:#ec4899;
                text-transform:uppercase;
                letter-spacing:0.5px
              ">
                📍 DELIVERY ADDRESS
              </div>
              <div style="
                color:#0f172a;
                font-size:11px;
                line-height:1.4;
                font-weight:500
              ">${customerData.address}</div>
            </div>
          `;
        }
        console.log(checkoutData)
        if (checkoutData?.services?.length) {
          innerHTML += `
            <div>
              <div style="
                display:flex;
                align-items:center;
                justify-content: space-between;
                gap:4px;
                margin-bottom:4px;
                font-size:9px;
                font-weight:600;
                color:#3b82f6;
                text-transform:uppercase;
                letter-spacing:0.5px
              ">
                🛍️ SERVICES
              </div>
              <div style="display:flex;flex-direction:column;gap:4px">
                ${checkoutData.services.map(s => `
                  <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                  ">
                    <span style="
                      color:#0f172a;
                      font-size:11px;
                      font-weight:500
                    ">${s.name}</span>
                    <span style="
                      background:#10b981;
                      color:white;
                      padding:2px 8px;
                      border-radius:4px;
                      font-size:11px;
                      font-weight:700
                    ">$${s.price}</span>
                  </div>
                `).join('')}
              </div>
              <div style="
                  display:flex;
                  align-items:center;
                  justify-content: space-between;
                  gap:4px;
                  margin-top:6px;
                  margin-bottom:2px;
                  font-size:9px;
                  font-weight:600;
                  color:#3b82f6;
                  text-transform:uppercase;
                  letter-spacing:0.5px;
              ">
                  Taxes & fees: <span style="color:#0f172a">${checkoutData?.totals.fees ?? 0.00}</span>
              </div>
              <div style="
                  display:flex;
                  align-items:center;
                  justify-content: space-between;
                  gap:4px;
                  margin-top:6px;
                  margin-bottom:2px;
                  font-size:9px;
                  font-weight:600;
                  color:#3b82f6;
                  text-transform:uppercase;
                  letter-spacing:0.5px;
              ">
                  💳 ⚡ Payment Option: <span style="color:#0f172a">${checkoutData.priceMode ?? 'BASE'}</span>
              </div>
            </div>
          `;
        }

        if (checkoutData?.totals) {
          innerHTML += `
            <div style="
              background:#3b82f6;
              color:#fff;
              border-radius:8px;
              padding:8px;
              text-align:center;
              margin-top:2px
            ">
              <div style="
                font-size:9px;
                font-weight:600;
                letter-spacing:1px;
                opacity:0.95;
                margin-bottom:2px
              ">TOTAL AMOUNT</div>
              <div style="font-size:20px;font-weight:700;line-height:1">
                $${checkoutData.totals.total}
              </div>
              ${checkoutData.totals.tax ? `
                <div style="opacity:0.85;font-size:10px;margin-top:3px">Including tax: $${checkoutData.totals.tax}</div>
              ` : ''}
            </div>
          `;
        }

        checkoutBox.innerHTML = innerHTML;
      }

      /* ================= Toggle Button ================= */
      const toggleBtn = document.createElement('button');
      toggleBtn.innerHTML = '👁️ Reveal Card Details';
      Object.assign(toggleBtn.style, {
        width: '100%',
        padding: '7px',
        borderRadius: '8px',
        border: '1.5px solid #3b82f6',
        background: 'white',
        color: '#3b82f6',
        fontWeight: '600',
        fontSize: '12px',
        cursor: 'pointer',
        transition: 'all 0.2s',
        marginTop: '2px'
      });

      toggleBtn.onmouseover = () => {
        toggleBtn.style.background = '#3b82f6';
        toggleBtn.style.color = 'white';
      };
      toggleBtn.onmouseout = () => {
        if (!isVisible) {
          toggleBtn.style.background = 'white';
          toggleBtn.style.color = '#3b82f6';
        }
      };

      toggleBtn.onclick = () => {
        isVisible = !isVisible;
        toggleBtn.innerHTML = isVisible ? '🔒 Hide Card Details' : '👁️ Reveal Card Details';
        toggleBtn.style.background = isVisible ? '#3b82f6' : 'white';
        toggleBtn.style.color = isVisible ? 'white' : '#3b82f6';
        updateView();

        if (isVisible) {
          setTimeout(() => {
            isVisible = false;
            toggleBtn.innerHTML = '👁️ Reveal Card Details';
            toggleBtn.style.background = 'white';
            toggleBtn.style.color = '#3b82f6';
            updateView();
          }, 10000);
        }
      };

      /* ================= Assemble ================= */
      content.append(cardUI);
      if (checkoutBox) content.append(checkoutBox);

      box.append(header, content, toggleBtn);
      overlay.appendChild(box);
      document.body.appendChild(overlay);
    }






    let filteredCustomers = [];
    let currentPage = 1;
    let rowsPerPage = 20;
    let customers = [];
    let searchText = '';

    $("#search").on("keyup", function () {

        searchText = $(this).val().trim();

        currentPage = 1;
        loadCustomers(1);
    });
    // Load customers
    window.loadCustomers = function(page = 1) {

      $("#customerTable").html(`
          <tr>
              <td colspan="7" class="table-loader">
                  <div class="loader"></div>
                  <span>Loading customers...</span>
              </td>
          </tr>
      `);

      $.ajax({
          url: "<?=$baseUrl?>/customer_card.php",
          type: "GET",
          dataType: "json",
          data: {
              page: page,
              limit: rowsPerPage,
              search: searchText
          },
          success: function(response) {

              if (!response.success) {
                  $("#customerTable").html(
                      `<tr><td colspan="7">Failed to load data</td></tr>`
                  );
                  return;
              }

              currentPage = response.currentPage;
              customers = response.data;

              $("#totalCustomers").text(response.totalRecords);

              renderTable();
              renderPagination(response.totalPages);
          },
          error: function() {
              $("#customerTable").html(
                  `<tr><td colspan="7">API Error</td></tr>`
              );
          }
      });
  };

    // Render table
    function renderTable() {

      let rows = "";

      if (customers.length === 0) {

        rows = `<tr><td colspan="7">No records found</td></tr>`;

      } else {

        $.each(customers, function (i, item) {

          const cardIcon = item.cardData
            ? `
                    <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" fill="none"
                        stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                  `
            : `
                    <svg width="24" height="24" viewBox="0 0 24 24"
                        fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2" y="4" width="20" height="16" rx="2" fill="#000"/>
                        <rect x="2" y="8" width="20" height="2" fill="#fff"/>
                        <path d="M9 6 L11 10 L9 13 L13 18"
                            stroke="#fff"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"/>
                        <line x1="15.5" y1="13.5" x2="18.5" y2="16.5"
                            stroke="#fff"
                            stroke-width="1.5"/>
                        <line x1="18.5" y1="13.5" x2="15.5" y2="16.5"
                            stroke="#fff"
                            stroke-width="1.5"/>
                    </svg>
                  `;

          rows += `
                <tr>
                    <td>${item.customerId || ''}</td>
                    <td>${item.name || ''}</td>
                    <td>${item.email || ''}</td>
                    <td>${item.phone || ''}</td>
                    <td>${item.address || ''}</td>
                    <td>${item.createdAt || ''}</td>
                    <td>
                        <button
                            style="border:none;background:none;cursor:pointer;"
                            ${item.cardData ? '' : 'disabled'}
                            title="${item.cardData ? 'View Card Details' : 'No Card Data'}"
                            class="btn btn-sm btn-primary view-card"
                            data-id="${item.customerId}"
                            data-checkout='${encodeURIComponent(JSON.stringify(item.checkoutDetails))}'
                            data-card='${encodeURIComponent(JSON.stringify(item.cardData))}'>
                            ${cardIcon}
                        </button>
                    </td>
                </tr>
            `;
        });
      }

      $("#customerTable").html(rows);
    }

    // Render pagination
    function renderPagination(totalPages) {

      let html = '<div class="custom-pagination">';

      html += `
          <button
              class="page-btn nav-btn"
              ${currentPage === 1 ? 'disabled' : ''}
              onclick="loadCustomers(${currentPage - 1})">
              ← Prev
          </button>
      `;

      for (let i = 1; i <= totalPages; i++) {
          html += `
              <button
                  class="page-btn ${i === currentPage ? 'active' : ''}"
                  onclick="loadCustomers(${i})" ${i === currentPage ? 'disabled' : ''}>
                  ${i}
              </button>
          `;
      }

      html += `
          <button
              class="page-btn nav-btn"
              ${currentPage === totalPages ? 'disabled' : ''}
              onclick="loadCustomers(${currentPage + 1})">
              Next →
          </button>
      `;

      html += '</div>';

      $("#pagination").html(html);
  }

    // Initial load
    $(document).ready(function () {
      loadCustomers(1);
    });

    // 🔹 Pagination click
    $(document).on("click", ".pagination-btn", function () {
      currentPage = parseInt($(this).data("page"));
      renderTable();
      renderPagination();
    });

    $(document).on('click', '.view-card', function () {
      const btn = $(this);
      const customerId = btn.data('id');
      const raw = btn.attr('data-card');
      const checkout = btn.attr('data-checkout');
      btn.prop('disabled', true);

      const cardData = JSON.parse(decodeURIComponent(raw));
      const cardDataraw = JSON.parse(decodeURIComponent(checkout));
      
      $.ajax({
        url: "<?=$baseUrl?>/decrypt.php",
        type: 'POST',
        dataType: 'json',
        data: { customerId, cardData },
        success: function (res) {
          btn.prop('disabled', false);
          if (!res.success) {
            alert(res.error || 'Failed to decrypt card');
            return;
          }
          showCustomAlert(res.card, cardDataraw);
        },
        error: function () {
          btn.prop('disabled', false);
          alert('Server error while decrypting card');
        }
      });
    });



    // 🔹 Live search (resets pagination)

  });



</script>


<?php  include __DIR__.'/../layout/footer.php'; ?>