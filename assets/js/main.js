/**
 * Online Voting System - Interactive Frontend Logic
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Auto-dismiss alerts after 5 seconds
  const autoAlerts = document.querySelectorAll('.alert-dismissible');
  autoAlerts.forEach(function (alert) {
    setTimeout(function () {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      if (bsAlert) bsAlert.close();
    }, 5000);
  });

  // 2. Clickable Candidate Cards on Ballot Page
  const candidateCards = document.querySelectorAll('.ballot-candidate-card');
  candidateCards.forEach(function (card) {
    card.addEventListener('click', function (e) {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'LABEL') {
        return;
      }

      const input = card.querySelector('input[type="radio"], input[type="checkbox"]');
      if (input) {
        if (input.type === 'radio') {
          input.checked = true;
          const groupName = input.name;
          document.querySelectorAll(`input[name="${groupName}"]`).forEach(function (radio) {
            const parentCard = radio.closest('.ballot-candidate-card');
            if (parentCard) parentCard.classList.remove('selected');
          });
          card.classList.add('selected');
        } else if (input.type === 'checkbox') {
          const maxVotes = parseInt(input.dataset.maxVotes || '1', 10);
          const groupName = input.name;
          const checkedCount = document.querySelectorAll(`input[name="${groupName}"]:checked`).length;

          if (!input.checked && checkedCount >= maxVotes) {
            alert(`You can only choose up to ${maxVotes} candidate(s) for this position.`);
            return;
          }

          input.checked = !input.checked;
          if (input.checked) {
            card.classList.add('selected');
          } else {
            card.classList.remove('selected');
          }
        }
      }
    });

    const input = card.querySelector('input[type="radio"], input[type="checkbox"]');
    if (input) {
      input.addEventListener('change', function () {
        if (input.type === 'radio') {
          const groupName = input.name;
          document.querySelectorAll(`input[name="${groupName}"]`).forEach(function (radio) {
            const parentCard = radio.closest('.ballot-candidate-card');
            if (parentCard) parentCard.classList.remove('selected');
          });
          if (input.checked) card.classList.add('selected');
        } else {
          const maxVotes = parseInt(input.dataset.maxVotes || '1', 10);
          const groupName = input.name;
          const checkedCount = document.querySelectorAll(`input[name="${groupName}"]:checked`).length;

          if (checkedCount > maxVotes) {
            input.checked = false;
            card.classList.remove('selected');
            alert(`You can only choose up to ${maxVotes} candidate(s) for this position.`);
            return;
          }

          if (input.checked) {
            card.classList.add('selected');
          } else {
            card.classList.remove('selected');
          }
        }
      });
    }
  });

  // 3. Live Table Search Filter
  const searchInputs = document.querySelectorAll('.table-search-input');
  searchInputs.forEach(function (input) {
    const tableId = input.dataset.targetTable;
    const table = document.getElementById(tableId);
    if (!table) return;

    input.addEventListener('keyup', function () {
      const query = this.value.toLowerCase().trim();
      const rows = table.querySelectorAll('tbody tr');

      rows.forEach(function (row) {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  });

  // 4. Ballot Submission Confirmation
  const ballotForm = document.getElementById('ballotForm');
  if (ballotForm) {
    ballotForm.addEventListener('submit', function (e) {
      const checkedVotes = ballotForm.querySelectorAll('input[type="radio"]:checked, input[type="checkbox"]:checked');
      if (checkedVotes.length === 0) {
        e.preventDefault();
        alert('Please select at least one candidate before submitting your ballot.');
        return false;
      }

      const confirmed = confirm(
        'Are you sure you want to cast your official ballot? Once submitted, your vote is permanent and cannot be modified.'
      );
      if (!confirmed) {
        e.preventDefault();
        return false;
      }
    });
  }
});
