using System.Text.Json;
using System.Text.Json.Serialization;
using System.Windows.Forms;

namespace DotPos.SchemaEditor;

internal sealed class SeedManagerDialog : Form
{
    private static readonly string[] SupportedSeedModes =
    [
        "ensure_missing_rows",
        "patch_existing_when_empty",
        "insert_all_if_table_empty"
    ];

    private static readonly JsonSerializerOptions StateJsonOptions = new()
    {
        DefaultIgnoreCondition = JsonIgnoreCondition.WhenWritingNull,
        WriteIndented = false
    };

    private readonly SchemaEditorRepository _repository;
    private readonly SplitContainer _mainSplitContainer = new() { Dock = DockStyle.Fill };
    private readonly ListBox _seedsListBox = new() { Dock = DockStyle.Fill, DrawMode = DrawMode.OwnerDrawFixed };
    private readonly Label _schemaPathLabel = new() { AutoEllipsis = true, Dock = DockStyle.Fill };
    private readonly Label _seedFileLabel = new() { AutoEllipsis = true, Dock = DockStyle.Fill };
    private readonly Label _versionLabel = new() { AutoSize = true };
    private readonly Label _statusLabel = new() { Dock = DockStyle.Fill, AutoEllipsis = true };
    private readonly TextBox _seedNameTextBox = new() { Dock = DockStyle.Fill };
    private readonly TextBox _versionKeyTextBox = new() { Dock = DockStyle.Fill };
    private readonly ComboBox _tableNameComboBox = new() { Dock = DockStyle.Fill, DropDownStyle = ComboBoxStyle.DropDownList };
    private readonly ComboBox _modeComboBox = new() { Dock = DockStyle.Fill, DropDownStyle = ComboBoxStyle.DropDownList };
    private readonly TextBox _matchColumnsTextBox = new() { Dock = DockStyle.Fill, ReadOnly = true };
    private readonly TextBox _patchColumnsTextBox = new() { Dock = DockStyle.Fill, ReadOnly = true };
    private readonly TextBox _zeroIsEmptyColumnsTextBox = new() { Dock = DockStyle.Fill, ReadOnly = true };
    private readonly Label _rowsSummaryLabel = new() { Dock = DockStyle.Fill, AutoEllipsis = true };

    private SchemaManifest _manifest;
    private SeedDocument? _currentDocument;
    private readonly Dictionary<string, SeedEditorState> _draftSeeds = new(StringComparer.OrdinalIgnoreCase);
    private readonly HashSet<string> _dirtySeeds = new(StringComparer.OrdinalIgnoreCase);
    private List<SeedRow> _workingRows = [];
    private bool _isLoading;

    public SeedManagerDialog(SchemaEditorRepository repository)
    {
        _repository = repository;
        _manifest = _repository.LoadManifest();

        Text = "DOT POS Seed Manager";
        Width = 1200;
        Height = 780;
        StartPosition = FormStartPosition.CenterParent;
        FormClosing += SeedManagerDialog_FormClosing;
        Shown += (_, _) => ApplyInitialLayout();

        BuildLayout();
        LoadSeeds();
    }

    private void BuildLayout()
    {
        _mainSplitContainer.FixedPanel = FixedPanel.Panel1;
        _mainSplitContainer.Panel1MinSize = 220;
        _mainSplitContainer.SplitterWidth = 8;

        _mainSplitContainer.Panel1.Controls.Add(BuildSeedListPanel());
        _mainSplitContainer.Panel2.Controls.Add(BuildEditorPanel());
        Controls.Add(_mainSplitContainer);
    }

    private void ApplyInitialLayout()
    {
        var desiredLeftWidth = Math.Max(240, Math.Min(300, ClientSize.Width / 5));
        if (_mainSplitContainer.SplitterDistance != desiredLeftWidth)
        {
            _mainSplitContainer.SplitterDistance = desiredLeftWidth;
        }
    }

    private Control BuildSeedListPanel()
    {
        var panel = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 3,
            Padding = new Padding(10)
        };
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        panel.RowStyles.Add(new RowStyle(SizeType.Percent, 100));
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));

        panel.Controls.Add(new Label
        {
            Text = "Seeds",
            AutoSize = true,
            Font = new Font(Font, FontStyle.Bold)
        }, 0, 0);

        _seedsListBox.SelectedIndexChanged += (_, _) => LoadSelectedSeed();
        _seedsListBox.DrawItem += SeedsListBox_DrawItem;
        panel.Controls.Add(_seedsListBox, 0, 1);

        var buttonPanel = new FlowLayoutPanel { Dock = DockStyle.Fill, AutoSize = true };
        var addButton = new Button { Text = "Add Seed", AutoSize = true };
        addButton.Click += (_, _) => AddSeed();
        var deleteButton = new Button { Text = "Delete Seed", AutoSize = true };
        deleteButton.Click += (_, _) => DeleteSelectedSeed();
        var reloadButton = new Button { Text = "Reload", AutoSize = true };
        reloadButton.Click += (_, _) => ReloadManifest();
        buttonPanel.Controls.Add(addButton);
        buttonPanel.Controls.Add(deleteButton);
        buttonPanel.Controls.Add(reloadButton);
        panel.Controls.Add(buttonPanel, 0, 2);
        return panel;
    }

    private Control BuildEditorPanel()
    {
        var panel = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 4,
            Padding = new Padding(10)
        };
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        panel.RowStyles.Add(new RowStyle(SizeType.Percent, 100));
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));

        var header = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 2,
            AutoSize = true
        };
        header.ColumnStyles.Add(new ColumnStyle(SizeType.AutoSize));
        header.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));
        header.Controls.Add(new Label { Text = "Schema Root:", AutoSize = true }, 0, 0);
        header.Controls.Add(_schemaPathLabel, 1, 0);
        header.Controls.Add(new Label { Text = "Seed File:", AutoSize = true }, 0, 1);
        header.Controls.Add(_seedFileLabel, 1, 1);
        header.Controls.Add(new Label { Text = "Version:", AutoSize = true }, 0, 2);
        header.Controls.Add(_versionLabel, 1, 2);
        _schemaPathLabel.Text = _repository.SchemaRoot;

        var actionPanel = new FlowLayoutPanel { Dock = DockStyle.Fill, AutoSize = true };
        var saveButton = new Button { Text = "Save Seed", AutoSize = true };
        saveButton.Click += (_, _) => SaveCurrentSeed();
        var closeButton = new Button { Text = "Close", AutoSize = true };
        closeButton.Click += (_, _) => Close();
        actionPanel.Controls.Add(saveButton);
        actionPanel.Controls.Add(closeButton);

        panel.Controls.Add(header, 0, 0);
        panel.Controls.Add(actionPanel, 0, 1);
        panel.Controls.Add(BuildEditorFields(), 0, 2);
        panel.Controls.Add(_statusLabel, 0, 3);
        return panel;
    }

    private Control BuildEditorFields()
    {
        var panel = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 2,
            RowCount = 8
        };
        panel.ColumnStyles.Add(new ColumnStyle(SizeType.AutoSize));
        panel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));
        for (var i = 0; i < 7; i++)
        {
            panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        }
        panel.RowStyles.Add(new RowStyle(SizeType.Percent, 100));

        _modeComboBox.Items.AddRange(SupportedSeedModes);
        WireEditorChangeTracking(_seedNameTextBox);
        WireEditorChangeTracking(_versionKeyTextBox);
        WireEditorChangeTracking(_tableNameComboBox);
        WireEditorChangeTracking(_modeComboBox);

        panel.Controls.Add(new Label { Text = "Seed Name:", AutoSize = true, Margin = new Padding(0, 6, 8, 6) }, 0, 0);
        panel.Controls.Add(_seedNameTextBox, 1, 0);
        panel.Controls.Add(new Label { Text = "Version Key:", AutoSize = true, Margin = new Padding(0, 6, 8, 6) }, 0, 1);
        panel.Controls.Add(_versionKeyTextBox, 1, 1);
        panel.Controls.Add(new Label { Text = "Table:", AutoSize = true, Margin = new Padding(0, 6, 8, 6) }, 0, 2);
        panel.Controls.Add(_tableNameComboBox, 1, 2);
        panel.Controls.Add(new Label { Text = "Mode:", AutoSize = true, Margin = new Padding(0, 6, 8, 6) }, 0, 3);
        panel.Controls.Add(_modeComboBox, 1, 3);
        panel.Controls.Add(new Label { Text = "Match Columns:", AutoSize = true, Margin = new Padding(0, 6, 8, 6) }, 0, 4);
        panel.Controls.Add(BuildSelectionField(_matchColumnsTextBox, SelectMatchColumns), 1, 4);
        panel.Controls.Add(new Label { Text = "Patch Empty Columns:", AutoSize = true, Margin = new Padding(0, 6, 8, 6) }, 0, 5);
        panel.Controls.Add(BuildSelectionField(_patchColumnsTextBox, SelectPatchColumns), 1, 5);
        panel.Controls.Add(new Label { Text = "Zero Is Empty Columns:", AutoSize = true, Margin = new Padding(0, 6, 8, 6) }, 0, 6);
        panel.Controls.Add(BuildSelectionField(_zeroIsEmptyColumnsTextBox, SelectZeroIsEmptyColumns), 1, 6);

        var rowsPanel = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 2
        };
        rowsPanel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        rowsPanel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        var editRowsButton = new Button { Text = "Edit Rows And Lookups...", AutoSize = true };
        editRowsButton.Click += (_, _) => EditRows();
        rowsPanel.Controls.Add(_rowsSummaryLabel, 0, 0);
        rowsPanel.Controls.Add(editRowsButton, 0, 1);

        panel.Controls.Add(new Label { Text = "Rows:", AutoSize = true, Margin = new Padding(0, 6, 8, 6) }, 0, 7);
        panel.Controls.Add(rowsPanel, 1, 7);
        return panel;
    }

    private Control BuildSelectionField(TextBox textBox, Action clickAction)
    {
        var panel = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 2,
            AutoSize = true
        };
        panel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));
        panel.ColumnStyles.Add(new ColumnStyle(SizeType.AutoSize));

        var button = new Button { Text = "Select...", AutoSize = true };
        button.Click += (_, _) => clickAction();
        panel.Controls.Add(textBox, 0, 0);
        panel.Controls.Add(button, 1, 0);
        return panel;
    }

    private void WireEditorChangeTracking(Control control)
    {
        switch (control)
        {
            case TextBox textBox:
                textBox.TextChanged += (_, _) => UpdateCurrentDirtyState();
                break;
            case ComboBox comboBox:
                comboBox.SelectedIndexChanged += (_, _) => UpdateCurrentDirtyState();
                comboBox.TextChanged += (_, _) => UpdateCurrentDirtyState();
                break;
        }
    }

    private void LoadSeeds()
    {
        _isLoading = true;
        try
        {
            _seedsListBox.BeginUpdate();
            _seedsListBox.Items.Clear();
            foreach (var seed in _repository.GetOrderedSeeds(_manifest))
            {
                _seedsListBox.Items.Add(seed);
            }
        }
        finally
        {
            _seedsListBox.EndUpdate();
            _isLoading = false;
        }

        if (_seedsListBox.Items.Count > 0 && _seedsListBox.SelectedIndex < 0)
        {
            _seedsListBox.SelectedIndex = 0;
        }
        else if (_seedsListBox.Items.Count == 0)
        {
            LoadSeedDocument(null);
        }
    }

    private void LoadSelectedSeed()
    {
        if (_isLoading)
        {
            return;
        }

        PersistCurrentDraft();
        if (_seedsListBox.SelectedItem is not SeedManifestEntry entry)
        {
            LoadSeedDocument(null);
            return;
        }

        var document = LoadSeedOrPlaceholder(entry, showError: true);
        if (_draftSeeds.TryGetValue(document.ManifestEntry.SeedName, out var draft))
        {
            LoadSeedDocument(document, draft);
            return;
        }

        LoadSeedDocument(document, SeedEditorState.FromDocument(document));
    }

    private void LoadSeedDocument(SeedDocument? document, SeedEditorState? state = null)
    {
        _currentDocument = document;
        _isLoading = true;
        try
        {
            var tableNames = _repository.GetOrderedTables(_manifest)
                .Select(table => table.TableName)
                .ToArray();
            _tableNameComboBox.BeginUpdate();
            _tableNameComboBox.Items.Clear();
            _tableNameComboBox.Items.AddRange(tableNames);
            _tableNameComboBox.EndUpdate();

            if (document == null || state == null)
            {
                _seedFileLabel.Text = string.Empty;
                _versionLabel.Text = string.Empty;
                _seedNameTextBox.Text = string.Empty;
                _versionKeyTextBox.Text = string.Empty;
                _tableNameComboBox.SelectedIndex = -1;
                _modeComboBox.SelectedIndex = -1;
                _matchColumnsTextBox.Text = string.Empty;
                _patchColumnsTextBox.Text = string.Empty;
                _zeroIsEmptyColumnsTextBox.Text = string.Empty;
                _workingRows = [];
                _rowsSummaryLabel.Text = string.Empty;
                return;
            }

            _seedFileLabel.Text = document.ManifestEntry.File;
            _versionLabel.Text = document.ManifestEntry.Version.ToString();
            _seedNameTextBox.Text = state.SeedName;
            _versionKeyTextBox.Text = state.VersionKeyName;
            _tableNameComboBox.SelectedItem = tableNames.Contains(state.TableName, StringComparer.OrdinalIgnoreCase)
                ? state.TableName
                : null;
            _modeComboBox.SelectedItem = SupportedSeedModes.Contains(state.Mode, StringComparer.OrdinalIgnoreCase)
                ? state.Mode
                : null;
            _matchColumnsTextBox.Text = state.MatchColumns;
            _patchColumnsTextBox.Text = state.PatchColumnsWhenEmpty;
            _zeroIsEmptyColumnsTextBox.Text = state.ZeroIsEmptyColumns;
            _workingRows = CloneRows(state.Rows);
            _rowsSummaryLabel.Text = BuildRowsSummary(_workingRows);
        }
        finally
        {
            _isLoading = false;
        }

        UpdateCurrentDirtyState();
    }

    private void AddSeed()
    {
        PersistCurrentDraft();
        var seedName = TextPromptDialog.Show(this, "Add Seed", "Seed name:");
        if (string.IsNullOrWhiteSpace(seedName))
        {
            return;
        }

        try
        {
            var document = _repository.AddSeed(_manifest, seedName);
            _manifest = _repository.LoadManifest();
            LoadSeeds();
            SelectSeed(document.ManifestEntry.SeedName);
            SetStatus($"Added seed '{document.ManifestEntry.SeedName}'.");
        }
        catch (Exception ex)
        {
            ShowError(ex.Message);
        }
    }

    private void DeleteSelectedSeed()
    {
        var entry = _currentDocument?.ManifestEntry ?? (_seedsListBox.SelectedItem as SeedManifestEntry);
        if (entry == null)
        {
            return;
        }

        var result = MessageBox.Show(this, $"Delete seed '{entry.SeedName}'?", "Delete Seed", MessageBoxButtons.YesNo, MessageBoxIcon.Warning);
        if (result != DialogResult.Yes)
        {
            return;
        }

        try
        {
            var seedName = entry.SeedName;
            _repository.DeleteSeed(_manifest, entry);
            _draftSeeds.Remove(seedName);
            _dirtySeeds.Remove(seedName);
            _manifest = _repository.LoadManifest();
            _currentDocument = null;
            LoadSeeds();
            SetStatus($"Deleted seed '{seedName}'.");
        }
        catch (Exception ex)
        {
            ShowError(ex.Message);
        }
    }

    private void ReloadManifest()
    {
        var selectedSeedName = _currentDocument?.ManifestEntry.SeedName;
        _manifest = _repository.LoadManifest();
        LoadSeeds();
        if (!string.IsNullOrWhiteSpace(selectedSeedName))
        {
            SelectSeed(selectedSeedName);
        }
        SetStatus("Reloaded seed manifest.");
    }

    private void SaveCurrentSeed()
    {
        if (_currentDocument == null)
        {
            return;
        }

        try
        {
            var originalSeedName = _currentDocument.ManifestEntry.SeedName;
            var (updatedEntry, updatedSchema) = BuildWorkingSeed();
            var result = _repository.SaveSeed(_manifest, _currentDocument, updatedEntry, updatedSchema);
            _draftSeeds.Remove(originalSeedName);
            _dirtySeeds.Remove(originalSeedName);
            _manifest = _repository.LoadManifest();
            LoadSeeds();
            SelectSeed(updatedEntry.SeedName);
            SetStatus(result.SeedChanged
                ? $"Saved seed '{updatedEntry.SeedName}'. Version {result.NewVersion}."
                : $"Seed '{updatedEntry.SeedName}' has no changes to save.");
        }
        catch (Exception ex)
        {
            ShowError(ex.Message);
        }
    }

    private void EditRows()
    {
        if (_currentDocument == null)
        {
            return;
        }

        var tableColumns = GetTableColumnNames(_tableNameComboBox.Text.Trim());
        var tableNames = _repository.GetOrderedTables(_manifest)
            .Select(table => table.TableName)
            .ToList();
        var updatedRows = SeedRowEditorDialog.EditRows(this, tableColumns, tableNames, _workingRows);
        if (updatedRows == null)
        {
            return;
        }

        _workingRows = CloneRows(updatedRows);
        _rowsSummaryLabel.Text = BuildRowsSummary(_workingRows);
        UpdateCurrentDirtyState();
    }

    private void SelectMatchColumns() => SelectColumnsIntoTextBox(_matchColumnsTextBox, "Select Match Columns", "Select one or more match columns.");
    private void SelectPatchColumns() => SelectColumnsIntoTextBox(_patchColumnsTextBox, "Select Patch Columns", "Select one or more columns to patch when empty.");
    private void SelectZeroIsEmptyColumns() => SelectColumnsIntoTextBox(_zeroIsEmptyColumnsTextBox, "Select Zero-Is-Empty Columns", "Select one or more columns where zero should count as empty.");

    private void SelectColumnsIntoTextBox(TextBox targetTextBox, string title, string prompt)
    {
        var tableName = _tableNameComboBox.Text.Trim();
        if (string.IsNullOrWhiteSpace(tableName))
        {
            ShowError("Choose a table before selecting columns.");
            return;
        }

        var availableColumns = GetTableColumnNames(tableName);
        if (availableColumns.Count == 0)
        {
            ShowError($"Table '{tableName}' does not have any selectable columns.");
            return;
        }

        var selectedColumns = ParseColumnList(targetTextBox.Text);
        var updatedColumns = ColumnMultiSelectDialog.SelectColumns(this, title, availableColumns, selectedColumns, prompt);
        if (updatedColumns == null)
        {
            return;
        }

        targetTextBox.Text = updatedColumns;
        UpdateCurrentDirtyState();
    }

    private List<string> GetTableColumnNames(string tableName)
    {
        var tableEntry = _manifest.Tables.SingleOrDefault(table => table.TableName.Equals(tableName, StringComparison.OrdinalIgnoreCase));
        if (tableEntry == null)
        {
            return [];
        }

        var tableDocument = _repository.LoadTable(tableEntry);
        return tableDocument.Schema.Columns
            .Where(column => !string.IsNullOrWhiteSpace(column.Name))
            .Select(column => column.Name.Trim())
            .Distinct(StringComparer.OrdinalIgnoreCase)
            .OrderBy(name => name, StringComparer.OrdinalIgnoreCase)
            .ToList();
    }

    private void PersistCurrentDraft()
    {
        if (_currentDocument == null)
        {
            return;
        }

        var currentState = CaptureCurrentState();
        var originalState = SeedEditorState.FromDocument(_currentDocument);
        var seedName = _currentDocument.ManifestEntry.SeedName;

        if (SeedEditorState.AreEquivalent(originalState, currentState))
        {
            _draftSeeds.Remove(seedName);
            _dirtySeeds.Remove(seedName);
        }
        else
        {
            _draftSeeds[seedName] = currentState;
            _dirtySeeds.Add(seedName);
        }

        _seedsListBox.Invalidate();
    }

    private void UpdateCurrentDirtyState()
    {
        if (_isLoading || _currentDocument == null)
        {
            return;
        }

        PersistCurrentDraft();
    }

    private SeedEditorState CaptureCurrentState()
    {
        return new SeedEditorState
        {
            SeedName = _seedNameTextBox.Text.Trim(),
            VersionKeyName = _versionKeyTextBox.Text.Trim(),
            TableName = _tableNameComboBox.Text.Trim(),
            Mode = _modeComboBox.Text.Trim(),
            MatchColumns = _matchColumnsTextBox.Text.Trim(),
            PatchColumnsWhenEmpty = _patchColumnsTextBox.Text.Trim(),
            ZeroIsEmptyColumns = _zeroIsEmptyColumnsTextBox.Text.Trim(),
            Rows = CloneRows(_workingRows)
        };
    }

    private (SeedManifestEntry Entry, SeedSchema Schema) BuildWorkingSeed()
    {
        if (_currentDocument == null)
        {
            throw new InvalidOperationException("No seed is currently loaded.");
        }

        var state = CaptureCurrentState();
        return (
            new SeedManifestEntry
            {
                SeedName = state.SeedName,
                File = _currentDocument.ManifestEntry.File,
                Version = _currentDocument.ManifestEntry.Version,
                VersionKeyName = state.VersionKeyName
            },
            new SeedSchema
            {
                TableName = state.TableName,
                Mode = state.Mode,
                MatchColumns = ParseColumnList(state.MatchColumns),
                PatchColumnsWhenEmpty = ParseColumnList(state.PatchColumnsWhenEmpty),
                ZeroIsEmptyColumns = ParseColumnList(state.ZeroIsEmptyColumns),
                Rows = CloneRows(state.Rows)
            });
    }

    private static List<string> ParseColumnList(string value)
    {
        return value
            .Split(',', StringSplitOptions.TrimEntries | StringSplitOptions.RemoveEmptyEntries)
            .ToList();
    }

    private void SeedManagerDialog_FormClosing(object? sender, FormClosingEventArgs e)
    {
        PersistCurrentDraft();
        if (_dirtySeeds.Count == 0)
        {
            return;
        }

        var prompt = _dirtySeeds.Count == 1
            ? $"You have unsaved changes in '{_dirtySeeds.First()}'. Save before closing?"
            : $"You have unsaved changes in {_dirtySeeds.Count} seeds. Save before closing?";
        var result = MessageBox.Show(this, prompt, "Unsaved Changes", MessageBoxButtons.YesNoCancel, MessageBoxIcon.Warning);
        if (result == DialogResult.Cancel)
        {
            e.Cancel = true;
            return;
        }

        if (result == DialogResult.No)
        {
            return;
        }

        if (!TrySaveAllDirtySeeds())
        {
            e.Cancel = true;
        }
    }

    private bool TrySaveAllDirtySeeds()
    {
        PersistCurrentDraft();
        var selectedSeedName = _currentDocument?.ManifestEntry.SeedName;
        foreach (var originalSeedName in _dirtySeeds.ToList())
        {
            if (!_draftSeeds.TryGetValue(originalSeedName, out var state))
            {
                continue;
            }

            var manifestEntry = _manifest.Seeds.SingleOrDefault(seed => seed.SeedName.Equals(originalSeedName, StringComparison.OrdinalIgnoreCase));
            if (manifestEntry == null)
            {
                ShowError($"Could not find manifest entry for dirty seed '{originalSeedName}'.");
                return false;
            }

            try
            {
                var originalDocument = _currentDocument != null &&
                                       _currentDocument.ManifestEntry.SeedName.Equals(originalSeedName, StringComparison.OrdinalIgnoreCase)
                    ? _currentDocument
                    : LoadSeedOrPlaceholder(manifestEntry, showError: false);

                _repository.SaveSeed(
                    _manifest,
                    originalDocument,
                    new SeedManifestEntry
                    {
                        SeedName = state.SeedName,
                        File = originalDocument.ManifestEntry.File,
                        Version = originalDocument.ManifestEntry.Version,
                        VersionKeyName = state.VersionKeyName
                    },
                    new SeedSchema
                    {
                        TableName = state.TableName,
                        Mode = state.Mode,
                        MatchColumns = ParseColumnList(state.MatchColumns),
                        PatchColumnsWhenEmpty = ParseColumnList(state.PatchColumnsWhenEmpty),
                        ZeroIsEmptyColumns = ParseColumnList(state.ZeroIsEmptyColumns),
                        Rows = CloneRows(state.Rows)
                    });

                _draftSeeds.Remove(originalSeedName);
                _dirtySeeds.Remove(originalSeedName);
                _manifest = _repository.LoadManifest();
            }
            catch (Exception ex)
            {
                ShowError(ex.Message);
                return false;
            }
        }

        LoadSeeds();
        if (!string.IsNullOrWhiteSpace(selectedSeedName))
        {
            SelectSeed(selectedSeedName);
        }

        return true;
    }

    private void SelectSeed(string seedName)
    {
        for (var i = 0; i < _seedsListBox.Items.Count; i++)
        {
            if (_seedsListBox.Items[i] is SeedManifestEntry seed &&
                seed.SeedName.Equals(seedName, StringComparison.OrdinalIgnoreCase))
            {
                _seedsListBox.SelectedIndex = i;
                return;
            }
        }
    }

    private void SeedsListBox_DrawItem(object? sender, DrawItemEventArgs e)
    {
        e.DrawBackground();
        if (e.Index < 0 || e.Index >= _seedsListBox.Items.Count)
        {
            return;
        }

        if (_seedsListBox.Items[e.Index] is not SeedManifestEntry seed)
        {
            return;
        }

        var isDirty = _dirtySeeds.Contains(seed.SeedName);
        var text = isDirty ? $"{seed.SeedName} *" : seed.SeedName;
        TextRenderer.DrawText(e.Graphics, text, e.Font, e.Bounds, isDirty ? Color.DarkOrange : e.ForeColor, TextFormatFlags.Left | TextFormatFlags.VerticalCenter);
        e.DrawFocusRectangle();
    }

    private void SetStatus(string message) => _statusLabel.Text = message;

    private void ShowError(string message)
    {
        MessageBox.Show(this, message, "Seed Manager", MessageBoxButtons.OK, MessageBoxIcon.Error);
        SetStatus(message);
    }

    private SeedDocument LoadSeedOrPlaceholder(SeedManifestEntry entry, bool showError)
    {
        try
        {
            return _repository.LoadSeed(entry);
        }
        catch (Exception ex)
        {
            var message = $"Seed '{entry.SeedName}' could not be loaded. The manifest entry still exists, but the seed file is missing or invalid.{Environment.NewLine}{Environment.NewLine}{ex.Message}";
            if (showError)
            {
                ShowError(message);
            }
            else
            {
                SetStatus(message);
            }

            return CreatePlaceholderSeedDocument(entry);
        }
    }

    private SeedDocument CreatePlaceholderSeedDocument(SeedManifestEntry entry)
    {
        var fallbackTableName = _repository.GetOrderedTables(_manifest).Select(table => table.TableName).FirstOrDefault() ?? string.Empty;
        return new SeedDocument
        {
            ManifestEntry = new SeedManifestEntry
            {
                SeedName = entry.SeedName,
                File = entry.File,
                Version = entry.Version,
                VersionKeyName = entry.VersionKeyName
            },
            Schema = new SeedSchema
            {
                TableName = fallbackTableName,
                Mode = SupportedSeedModes[0],
                MatchColumns = [],
                PatchColumnsWhenEmpty = [],
                ZeroIsEmptyColumns = [],
                Rows =
                [
                    new SeedRow
                    {
                        Values = new Dictionary<string, JsonElement>(StringComparer.OrdinalIgnoreCase)
                        {
                            ["example_key"] = JsonSerializer.SerializeToElement("value")
                        },
                        Lookups = []
                    }
                ]
            },
            SeedPath = ResolveSeedPath(entry)
        };
    }

    private string ResolveSeedPath(SeedManifestEntry entry)
    {
        var relativePath = string.IsNullOrWhiteSpace(entry.File) ? Path.Combine("seeds", $"{entry.SeedName}.seed.json") : entry.File.Replace('/', Path.DirectorySeparatorChar);
        var fullPath = Path.GetFullPath(Path.Combine(_repository.SchemaRoot, relativePath));
        return fullPath.StartsWith(_repository.SchemaRoot, StringComparison.OrdinalIgnoreCase)
            ? fullPath
            : Path.Combine(_repository.SchemaRoot, "seeds", $"{entry.SeedName}.seed.json");
    }

    private static List<SeedRow> CloneRows(IEnumerable<SeedRow> rows) => rows.Select(CloneSeedRow).ToList();

    private static SeedRow CloneSeedRow(SeedRow row)
    {
        return new SeedRow
        {
            Values = row.Values.ToDictionary(pair => pair.Key, pair => pair.Value.Clone(), StringComparer.OrdinalIgnoreCase),
            Lookups = row.Lookups.Select(lookup => new SeedLookup
            {
                TargetColumn = lookup.TargetColumn,
                LookupTable = lookup.LookupTable,
                LookupColumn = lookup.LookupColumn,
                SelectColumn = lookup.SelectColumn,
                LookupValue = lookup.LookupValue.Clone(),
                Required = lookup.Required
            }).ToList()
        };
    }

    private static string BuildRowsSummary(IEnumerable<SeedRow> rows)
    {
        var rowList = rows.ToList();
        return $"{rowList.Count} row(s), {rowList.Sum(row => row.Values.Count)} value(s), {rowList.Sum(row => row.Lookups.Count)} lookup(s)";
    }

    private sealed class SeedEditorState
    {
        public string SeedName { get; init; } = string.Empty;
        public string VersionKeyName { get; init; } = string.Empty;
        public string TableName { get; init; } = string.Empty;
        public string Mode { get; init; } = string.Empty;
        public string MatchColumns { get; init; } = string.Empty;
        public string PatchColumnsWhenEmpty { get; init; } = string.Empty;
        public string ZeroIsEmptyColumns { get; init; } = string.Empty;
        public List<SeedRow> Rows { get; init; } = [];

        public static SeedEditorState FromDocument(SeedDocument document)
        {
            return new SeedEditorState
            {
                SeedName = document.ManifestEntry.SeedName,
                VersionKeyName = document.ManifestEntry.VersionKeyName,
                TableName = document.Schema.TableName,
                Mode = document.Schema.Mode,
                MatchColumns = string.Join(", ", document.Schema.MatchColumns),
                PatchColumnsWhenEmpty = string.Join(", ", document.Schema.PatchColumnsWhenEmpty),
                ZeroIsEmptyColumns = string.Join(", ", document.Schema.ZeroIsEmptyColumns),
                Rows = CloneRows(document.Schema.Rows)
            };
        }

        public static bool AreEquivalent(SeedEditorState left, SeedEditorState right)
        {
            return string.Equals(
                JsonSerializer.Serialize(left, StateJsonOptions),
                JsonSerializer.Serialize(right, StateJsonOptions),
                StringComparison.Ordinal);
        }
    }
}
