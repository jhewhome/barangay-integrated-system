<?php
if (defined('BHC_FOLLOWUP_APPT_STYLES')) {
    return;
}
define('BHC_FOLLOWUP_APPT_STYLES', true);
?>
<style>
  .followup-appt-card {
    margin-bottom: 12px;
    padding: 14px 16px;
    border-radius: 14px;
    border: 1px solid rgba(47, 107, 255, 0.25);
    background: linear-gradient(135deg, rgba(47, 107, 255, 0.1), rgba(47, 107, 255, 0.04));
  }
  .followup-appt-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
  }
  .followup-appt-heading {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    min-width: 0;
  }
  .followup-appt-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.75);
    font-size: 18px;
    flex: 0 0 auto;
  }
  .followup-appt-title {
    font-weight: 700;
    font-size: 15px;
    line-height: 1.3;
  }
  .followup-appt-subtitle {
    margin-top: 2px;
    font-size: 13px;
    color: var(--muted);
  }
  .followup-appt-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: flex-end;
  }
  .followup-appt-patient {
    margin-bottom: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.55);
    border: 1px solid rgba(15, 23, 42, 0.06);
  }
  .followup-appt-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px 14px;
  }
  .followup-appt-item.span-2 {
    grid-column: 1 / -1;
  }
  .followup-appt-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 4px;
  }
  .followup-appt-value {
    font-size: 14px;
    line-height: 1.45;
  }
  .followup-appt-footer {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
    font-size: 12px;
    color: var(--muted);
    line-height: 1.5;
  }
  .followup-appt-actions {
    margin-top: 12px;
  }
  @media (max-width: 640px) {
    .followup-appt-grid {
      grid-template-columns: 1fr;
    }
    .followup-appt-header {
      flex-direction: column;
    }
    .followup-appt-badges {
      justify-content: flex-start;
    }
  }
</style>
