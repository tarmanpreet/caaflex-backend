```markdown
# Design System Document: The Precision Curator

## 1. Overview & Creative North Star
**Creative North Star: "The Digital Atelier"**
In the complex world of tax assistance (CAAF), the interface must act as a master curator—organizing chaos into a serene, high-end editorial experience. We are moving away from the "industrial spreadsheet" look of legacy financial software. Instead, this design system treats data as a premium asset. By utilizing intentional asymmetry, expansive breathing room, and sophisticated tonal layering, we transform a data-heavy environment into a "light" and intuitive workspace. The goal is to make the tax professional feel like an architect of information, not just a data entry clerk.

---

## 2. Colors & Surface Philosophy
The palette is rooted in trust, using deep blues and architectural grays. However, the *application* of these colors is what creates the premium feel.

### The "No-Line" Rule
Traditional borders create visual noise. This design system **prohibits 1px solid borders** for sectioning. Boundaries must be defined through background color shifts. For instance, a `surface-container-low` section sitting on a `surface` background creates a clear but soft structural division.

### Surface Hierarchy & Nesting
Treat the UI as a series of physical layers. We use the surface-container tiers to define importance:
- **Level 1 (Foundation):** `surface` (#f8f9fa)
- **Level 2 (Sectioning):** `surface-container-low` (#f1f4f6)
- **Level 3 (Workspaces):** `surface-container` (#eaeff1)
- **Level 4 (Active Elements):** `surface-container-lowest` (#ffffff)

### The "Glass & Gradient" Rule
For floating elements or modal overlays, use **Glassmorphism**. Apply `surface-container-lowest` at 80% opacity with a `24px` backdrop blur. 
- **Signature Texture:** Primary CTAs should not be flat. Use a subtle linear gradient from `primary` (#0056d2) to `primary_dim` (#004bb9) at a 135-degree angle to provide a "jewel-toned" depth that feels authoritative.

---

## 3. Typography
We use a dual-font strategy to balance editorial elegance with extreme legibility.

- **Display & Headlines (Manrope):** Chosen for its modern, geometric structure. Used for page titles and section headers to provide a "high-end magazine" feel.
  - *Headline-lg:* `manrope`, 2rem. Use for page titles to establish clear hierarchy.
- **Body & Labels (Inter):** The workhorse for data. Inter’s tall x-height ensures that complex tax forms remain readable at smaller scales.
  - *Body-md:* `inter`, 0.875rem. The default for all form inputs and table data.
  - *Label-sm:* `inter`, 0.6875rem. Used for metadata and overlines, always in `on_surface_variant` (#586064).

---

## 4. Elevation & Depth
Depth is achieved through **Tonal Layering** rather than traditional structural lines.

- **The Layering Principle:** To lift a card, place a `surface-container-lowest` (#ffffff) card on top of a `surface-container-low` (#f1f4f6) background. This creates a natural, soft lift.
- **Ambient Shadows:** For "Global Actions" (like a floating save button), use an extra-diffused shadow: `0px 20px 40px rgba(12, 15, 16, 0.06)`. The shadow color is a tinted version of `inverse_surface` to mimic natural light.
- **The "Ghost Border" Fallback:** If a border is required for accessibility in input fields, use `outline_variant` (#abb3b7) at **20% opacity**. Never use 100% opaque borders.

---

## 5. Components

### Modern Data Tables
- **Layout:** Forbid divider lines. Use `surface-container-low` for the header row and alternating `surface` and `surface-container-lowest` for rows (zebra striping is too heavy; use subtle tonal shifts).
- **Padding:** High vertical padding (16px+) to give data "room to breathe."

### Progress Indicators (The "Pathfinder")
- Instead of a standard stepper, use a "ghost-filled" bar. The track uses `surface-container-highest`, while the progress is a gradient of `primary` to `primary_fixed`.
- Active steps use `title-sm` typography; inactive steps use `label-md` with `on_surface_variant`.

### Input Fields
- **Styling:** Use a "filled" style with a `surface-container-high` background and a `2px` bottom-only indicator in `primary` on focus.
- **Corner Radius:** Use the `md` (0.375rem) scale for a professional, slightly softened look.

### Status Badges
- **Success:** `on_primary_container` text on a `primary_container` background.
- **Error:** `on_error_container` text on an `error_container` background.
- **Shape:** Use the `full` (9999px) roundedness scale for badges to distinguish them from square buttons.

### Primary Buttons
- **Style:** Linear gradient (`primary` to `primary_dim`), `xl` (0.75rem) roundedness.
- **Interaction:** On hover, shift the gradient intensity. Avoid heavy drop shadows; use a subtle `surface_tint` glow.

---

## 6. Do's and Don'ts

### Do
- **Do** use whitespace as a separator. If you think you need a line, try adding 16px of padding first.
- **Do** use `inter` for anything numeric. It is optimized for tabular numbers.
- **Do** stack surfaces (e.g., White card on Gray background) to show containment.
- **Do** use `surface_bright` for main content areas to keep the "light" feel.

### Don'ts
- **Don't** use pure black (#000000) for text. Use `on_surface` (#2b3437) to maintain a premium, softer contrast.
- **Don't** use 1px borders to separate table rows or sidebar sections.
- **Don't** use standard "Blue" links. Use `primary` (#0056d2) with a medium weight font to signify interactivity.
- **Don't** crowd the layout. In tax software, the urge is to fit everything on one screen. Resist this; use the `surface-container` nesting to hide/show secondary information.

---

## 7. Signature Interaction: The "Focus Frost"
When a user clicks into a complex form section, the surrounding sections should receive a very light `backdrop-blur` (2px) and a shift to `surface_dim`. This creates a "spotlight" effect, reducing cognitive load and helping the user focus on the data at hand.```