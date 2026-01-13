## Implementazione Finale: `_tooltip.html.twig`

La macro è stata implementata e standardizzata per gestire sia bottoni (`<a>`, `<button>`) che badge, risolvendo bug di centratura e artefatti visivi.

### Struttura Macro
```twig
{# templates/_tooltip.html.twig #}
{% macro button(label, content, icon, path, options) %}
    <div class="tooltip" data-tip="{{ content }}">
        <button type="submit" class="inline-flex items-center justify-center {{ options.class|default('btn btn-xs') }}">
            {{ include(icon, { dim: options.dim|default(20) }) }}
        </button>
    </div>
{% endmacro %}

{% macro link(label, content, icon, path, options) %}
    <div class="tooltip" data-tip="{{ content }}">
        <a href="{{ path }}" class="inline-flex items-center justify-center {{ options.class|default('btn btn-xs') }}">
            {{ include(icon, { dim: options.dim|default(20) }) }}
        </a>
    </div>
{% endmacro %}
```

### Regole Tattiche di Utilizzo
1. **Centratura Forzata**: Tutte le macro utilizzano `inline-flex items-center justify-center` per prevenire artefatti a "capsula" dietro le icone.
2. **Dimensionamento Icone**: Utilizzare `dim: 18` per le icone grid e `dim: 24` per le azioni principali di intestazione.
3. **Micro-copy bridge**:
   - `View details` → `INSPECT_DATA`
   - `Edit record` → `CALIBRATE_SYSTEM`
   - `Delete record` → `PURGE_LOG`
4. **Tooltips non ridondanti**: Il `data-tip` deve aggiungere contesto operativo, non ripetere il nome del pulsante.
