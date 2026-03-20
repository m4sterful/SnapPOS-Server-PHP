using System.ComponentModel;
using System.Text.Json;
using System.Windows.Forms;

namespace DotPos.SchemaEditor;

internal sealed class SeedRowEditorDialog : Form
{
    private static readonly string[] ValueTypeOptions =
    [
        "String",
        "Number",
        "Boolean",
        "Null",
        "JSON"
    ];

    private readonly List<string> _tableColumns;
    private readonly List<string> _tableNames;
    private readonly ListBox _rowsListBox = new() { Dock = DockStyle.Fill };
    private readonly DataGridView _valuesGrid = new() { Dock = DockStyle.Fill, AutoGenerateColumns = false };
    private readonly DataGridView _lookupsGrid = new() { Dock = DockStyle.Fill, AutoGenerateColumns = false };
    private readonly List<SeedRow> _rows;
    private BindingList<ValueEditorRow> _valueRows = [];
    private BindingList<LookupEditorRow> _lookupRows = [];
    private bool _isLoading;

    private SeedRowEditorDialog(IReadOnlyList<string> tableColumns, IReadOnlyList<string> tableNames, IReadOnlyList<SeedRow> rows)
    {
        _tableColumns = tableColumns.ToList();
        _tableNames = tableNames.ToList();
        _rows = rows.Select(CloneSeedRow).ToList();

        Text = "Edit Seed Rows";
        Width = 1200;
        Height = 760;
        StartPosition = FormStartPosition.CenterParent;

        BuildLayout();
        RefreshRowsList();
    }

    public static List<SeedRow>? EditRows(IWin32Window owner, IReadOnlyList<string> tableColumns, IReadOnlyList<string> tableNames, IReadOnlyList<SeedRow> rows)
    {
        using var dialog = new SeedRowEditorDialog(tableColumns, tableNames, rows);
        return dialog.ShowDialog(owner) == DialogResult.OK
            ? dialog._rows.Select(CloneSeedRow).ToList()
            : null;
    }

    private void BuildLayout()
    {
        ConfigureValuesGrid();
        ConfigureLookupsGrid();

        var root = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 2,
            Padding = new Padding(10)
        };
        root.RowStyles.Add(new RowStyle(SizeType.Percent, 100));
        root.RowStyles.Add(new RowStyle(SizeType.AutoSize));

        var split = new SplitContainer
        {
            Dock = DockStyle.Fill,
            FixedPanel = FixedPanel.Panel1,
            Panel1MinSize = 220,
            SplitterDistance = 250
        };

        var left = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 3
        };
        left.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        left.RowStyles.Add(new RowStyle(SizeType.Percent, 100));
        left.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        left.Controls.Add(new Label { Text = "Rows", AutoSize = true, Font = new Font(Font, FontStyle.Bold) }, 0, 0);
        _rowsListBox.SelectedIndexChanged += (_, _) => LoadSelectedRow();
        left.Controls.Add(_rowsListBox, 0, 1);

        var rowButtons = new FlowLayoutPanel { Dock = DockStyle.Fill, AutoSize = true };
        var addRowButton = new Button { Text = "Add Row", AutoSize = true };
        addRowButton.Click += (_, _) => AddRow();
        var removeRowButton = new Button { Text = "Remove Row", AutoSize = true };
        removeRowButton.Click += (_, _) => RemoveSelectedRow();
        rowButtons.Controls.Add(addRowButton);
        rowButtons.Controls.Add(removeRowButton);
        left.Controls.Add(rowButtons, 0, 2);
        split.Panel1.Controls.Add(left);

        var right = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 2
        };
        right.RowStyles.Add(new RowStyle(SizeType.Percent, 50));
        right.RowStyles.Add(new RowStyle(SizeType.Percent, 50));
        right.Controls.Add(BuildGridPanel("Values", _valuesGrid, AddValue, RemoveSelectedValue), 0, 0);
        right.Controls.Add(BuildGridPanel("Lookups", _lookupsGrid, AddLookup, RemoveSelectedLookup), 0, 1);
        split.Panel2.Controls.Add(right);

        var buttonPanel = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            FlowDirection = FlowDirection.RightToLeft,
            AutoSize = true
        };
        var okButton = new Button { Text = "OK", AutoSize = true };
        okButton.Click += (_, _) => ConfirmSave();
        var cancelButton = new Button { Text = "Cancel", AutoSize = true, DialogResult = DialogResult.Cancel };
        buttonPanel.Controls.Add(okButton);
        buttonPanel.Controls.Add(cancelButton);
        AcceptButton = okButton;
        CancelButton = cancelButton;

        root.Controls.Add(split, 0, 0);
        root.Controls.Add(buttonPanel, 0, 1);
        Controls.Add(root);
    }

    private Control BuildGridPanel(string title, DataGridView grid, Action addAction, Action removeAction)
    {
        var panel = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 3,
            Padding = new Padding(0, 6, 0, 0)
        };
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        panel.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        panel.RowStyles.Add(new RowStyle(SizeType.Percent, 100));

        panel.Controls.Add(new Label { Text = title, AutoSize = true, Font = new Font(Font, FontStyle.Bold) }, 0, 0);

        var buttons = new FlowLayoutPanel { Dock = DockStyle.Fill, AutoSize = true };
        var addButton = new Button { Text = $"Add {title[..^1]}", AutoSize = true };
        addButton.Click += (_, _) => addAction();
        var removeButton = new Button { Text = $"Remove Selected {title[..^1]}", AutoSize = true };
        removeButton.Click += (_, _) => removeAction();
        buttons.Controls.Add(addButton);
        buttons.Controls.Add(removeButton);

        panel.Controls.Add(buttons, 0, 1);
        panel.Controls.Add(grid, 0, 2);
        return panel;
    }

    private void ConfigureValuesGrid()
    {
        _valuesGrid.AllowUserToAddRows = false;
        _valuesGrid.AllowUserToDeleteRows = false;
        _valuesGrid.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
        _valuesGrid.MultiSelect = false;
        _valuesGrid.DataError += HandleGridDataError;
        _valuesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Column",
            DataPropertyName = nameof(ValueEditorRow.Key),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 28
        });
        _valuesGrid.Columns.Add(new DataGridViewComboBoxColumn
        {
            HeaderText = "Type",
            DataPropertyName = nameof(ValueEditorRow.ValueType),
            DataSource = ValueTypeOptions,
            AutoSizeMode = DataGridViewAutoSizeColumnMode.AllCells
        });
        _valuesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Value",
            DataPropertyName = nameof(ValueEditorRow.ValueText),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 72
        });
    }

    private void ConfigureLookupsGrid()
    {
        _lookupsGrid.AllowUserToAddRows = false;
        _lookupsGrid.AllowUserToDeleteRows = false;
        _lookupsGrid.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
        _lookupsGrid.MultiSelect = false;
        _lookupsGrid.DataError += HandleGridDataError;
        _lookupsGrid.Columns.Add(new DataGridViewComboBoxColumn
        {
            HeaderText = "Target Column",
            DataPropertyName = nameof(LookupEditorRow.TargetColumn),
            DataSource = _tableColumns,
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 16
        });
        _lookupsGrid.Columns.Add(new DataGridViewComboBoxColumn
        {
            HeaderText = "Lookup Table",
            DataPropertyName = nameof(LookupEditorRow.LookupTable),
            DataSource = _tableNames,
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 16
        });
        _lookupsGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Lookup Column",
            DataPropertyName = nameof(LookupEditorRow.LookupColumn),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 14
        });
        _lookupsGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Select Column",
            DataPropertyName = nameof(LookupEditorRow.SelectColumn),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 14
        });
        _lookupsGrid.Columns.Add(new DataGridViewComboBoxColumn
        {
            HeaderText = "Value Type",
            DataPropertyName = nameof(LookupEditorRow.LookupValueType),
            DataSource = ValueTypeOptions,
            AutoSizeMode = DataGridViewAutoSizeColumnMode.AllCells
        });
        _lookupsGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Lookup Value",
            DataPropertyName = nameof(LookupEditorRow.LookupValueText),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 24
        });
        _lookupsGrid.Columns.Add(new DataGridViewCheckBoxColumn
        {
            HeaderText = "Required",
            DataPropertyName = nameof(LookupEditorRow.Required),
            Width = 75
        });
    }

    private void HandleGridDataError(object? sender, DataGridViewDataErrorEventArgs e)
    {
        e.Cancel = true;
        MessageBox.Show(this, "One or more row values are invalid.", "Seed Rows", MessageBoxButtons.OK, MessageBoxIcon.Error);
    }

    private void RefreshRowsList()
    {
        var selectedIndex = _rowsListBox.SelectedIndex;
        _isLoading = true;
        try
        {
            _rowsListBox.BeginUpdate();
            _rowsListBox.Items.Clear();
            foreach (var row in _rows)
            {
                _rowsListBox.Items.Add(new RowListItem(row));
            }
        }
        finally
        {
            _rowsListBox.EndUpdate();
            _isLoading = false;
        }

        if (_rows.Count == 0)
        {
            LoadRowDetails(null);
            return;
        }

        _rowsListBox.SelectedIndex = selectedIndex >= 0 && selectedIndex < _rows.Count ? selectedIndex : 0;
    }

    private void LoadSelectedRow()
    {
        if (_isLoading)
        {
            return;
        }

        SaveCurrentRowDetails();
        if (_rowsListBox.SelectedIndex < 0 || _rowsListBox.SelectedIndex >= _rows.Count)
        {
            LoadRowDetails(null);
            return;
        }

        LoadRowDetails(_rows[_rowsListBox.SelectedIndex]);
    }

    private void LoadRowDetails(SeedRow? row)
    {
        _isLoading = true;
        try
        {
            _valueRows = new BindingList<ValueEditorRow>(row == null ? [] : row.Values.Select(ToValueRow).ToList());
            _lookupRows = new BindingList<LookupEditorRow>(row == null ? [] : row.Lookups.Select(ToLookupRow).ToList());
            _valuesGrid.DataSource = _valueRows;
            _lookupsGrid.DataSource = _lookupRows;
        }
        finally
        {
            _isLoading = false;
        }
    }

    private void SaveCurrentRowDetails()
    {
        if (_isLoading || _rowsListBox.SelectedIndex < 0 || _rowsListBox.SelectedIndex >= _rows.Count)
        {
            return;
        }

        _rows[_rowsListBox.SelectedIndex] = new SeedRow
        {
            Values = BuildValuesDictionary(_valueRows),
            Lookups = BuildLookupsList(_lookupRows)
        };
        RefreshRowsList();
    }

    private void AddRow()
    {
        SaveCurrentRowDetails();
        _rows.Add(new SeedRow
        {
            Values = new Dictionary<string, JsonElement>(StringComparer.OrdinalIgnoreCase)
            {
                ["example_key"] = JsonSerializer.SerializeToElement("value")
            },
            Lookups = []
        });
        RefreshRowsList();
        _rowsListBox.SelectedIndex = _rows.Count - 1;
    }

    private void RemoveSelectedRow()
    {
        if (_rowsListBox.SelectedIndex < 0 || _rowsListBox.SelectedIndex >= _rows.Count)
        {
            return;
        }

        _rows.RemoveAt(_rowsListBox.SelectedIndex);
        RefreshRowsList();
    }

    private void AddValue()
    {
        _valueRows.Add(new ValueEditorRow { ValueType = "String" });
    }

    private void RemoveSelectedValue()
    {
        if (_valuesGrid.CurrentRow?.DataBoundItem is not ValueEditorRow row)
        {
            return;
        }

        _valueRows.Remove(row);
    }

    private void AddLookup()
    {
        _lookupRows.Add(new LookupEditorRow
        {
            SelectColumn = "id",
            LookupValueType = "String",
            Required = true
        });
    }

    private void RemoveSelectedLookup()
    {
        if (_lookupsGrid.CurrentRow?.DataBoundItem is not LookupEditorRow row)
        {
            return;
        }

        _lookupRows.Remove(row);
    }

    private void ConfirmSave()
    {
        try
        {
            SaveCurrentRowDetails();
            DialogResult = DialogResult.OK;
            Close();
        }
        catch (Exception ex)
        {
            MessageBox.Show(this, ex.Message, "Seed Rows", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private static Dictionary<string, JsonElement> BuildValuesDictionary(IEnumerable<ValueEditorRow> rows)
    {
        var values = new Dictionary<string, JsonElement>(StringComparer.OrdinalIgnoreCase);
        foreach (var row in rows)
        {
            if (row.IsBlank())
            {
                continue;
            }

            var key = row.Key.Trim();
            if (key.Length == 0)
            {
                throw new InvalidOperationException("Each value row must define a column name.");
            }

            if (!values.TryAdd(key, ParseEditorValue(row.ValueType, row.ValueText, $"Value '{key}'")))
            {
                throw new InvalidOperationException($"Duplicate value column '{key}' is not allowed within the same row.");
            }
        }

        return values;
    }

    private static List<SeedLookup> BuildLookupsList(IEnumerable<LookupEditorRow> rows)
    {
        var lookups = new List<SeedLookup>();
        foreach (var row in rows)
        {
            if (row.IsBlank())
            {
                continue;
            }

            var targetColumn = row.TargetColumn.Trim();
            var lookupTable = row.LookupTable.Trim();
            var lookupColumn = row.LookupColumn.Trim();
            var selectColumn = string.IsNullOrWhiteSpace(row.SelectColumn) ? "id" : row.SelectColumn.Trim();
            if (targetColumn.Length == 0 || lookupTable.Length == 0 || lookupColumn.Length == 0 || selectColumn.Length == 0)
            {
                throw new InvalidOperationException("Each lookup row must define target column, lookup table, lookup column, and select column.");
            }

            lookups.Add(new SeedLookup
            {
                TargetColumn = targetColumn,
                LookupTable = lookupTable,
                LookupColumn = lookupColumn,
                SelectColumn = selectColumn,
                LookupValue = ParseEditorValue(row.LookupValueType, row.LookupValueText, $"Lookup value for '{targetColumn}'"),
                Required = row.Required
            });
        }

        return lookups;
    }

    private static JsonElement ParseEditorValue(string valueType, string valueText, string context)
    {
        var type = string.IsNullOrWhiteSpace(valueType) ? "String" : valueType.Trim();
        switch (type.ToUpperInvariant())
        {
            case "STRING":
                return JsonSerializer.SerializeToElement(valueText ?? string.Empty);
            case "NUMBER":
                try
                {
                    using (var document = JsonDocument.Parse(valueText))
                    {
                        if (document.RootElement.ValueKind != JsonValueKind.Number)
                        {
                            throw new InvalidOperationException();
                        }

                        return document.RootElement.Clone();
                    }
                }
                catch
                {
                    throw new InvalidOperationException($"{context} must be a valid JSON number.");
                }
            case "BOOLEAN":
                if (!bool.TryParse(valueText, out var boolValue))
                {
                    throw new InvalidOperationException($"{context} must be 'true' or 'false'.");
                }

                return JsonSerializer.SerializeToElement(boolValue);
            case "NULL":
                using (var document = JsonDocument.Parse("null"))
                {
                    return document.RootElement.Clone();
                }
            case "JSON":
                try
                {
                    using var document = JsonDocument.Parse(valueText);
                    return document.RootElement.Clone();
                }
                catch (JsonException ex)
                {
                    throw new InvalidOperationException($"{context} must be valid JSON.", ex);
                }
            default:
                throw new InvalidOperationException($"Unsupported value type '{valueType}'.");
        }
    }

    private static ValueEditorRow ToValueRow(KeyValuePair<string, JsonElement> pair)
    {
        return new ValueEditorRow
        {
            Key = pair.Key,
            ValueType = DetermineValueType(pair.Value),
            ValueText = DetermineValueText(pair.Value)
        };
    }

    private static LookupEditorRow ToLookupRow(SeedLookup lookup)
    {
        return new LookupEditorRow
        {
            TargetColumn = lookup.TargetColumn,
            LookupTable = lookup.LookupTable,
            LookupColumn = lookup.LookupColumn,
            SelectColumn = lookup.SelectColumn,
            LookupValueType = DetermineValueType(lookup.LookupValue),
            LookupValueText = DetermineValueText(lookup.LookupValue),
            Required = lookup.Required
        };
    }

    private static string DetermineValueType(JsonElement element)
    {
        return element.ValueKind switch
        {
            JsonValueKind.String => "String",
            JsonValueKind.Number => "Number",
            JsonValueKind.True => "Boolean",
            JsonValueKind.False => "Boolean",
            JsonValueKind.Null => "Null",
            _ => "JSON"
        };
    }

    private static string DetermineValueText(JsonElement element)
    {
        return element.ValueKind switch
        {
            JsonValueKind.String => element.GetString() ?? string.Empty,
            JsonValueKind.True => "true",
            JsonValueKind.False => "false",
            JsonValueKind.Null => string.Empty,
            _ => element.GetRawText()
        };
    }

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

    private sealed class RowListItem(SeedRow row)
    {
        public override string ToString()
        {
            if (row.Values.Count == 0)
            {
                return $"Row ({row.Lookups.Count} lookup(s))";
            }

            var first = row.Values.First();
            return $"{first.Key} = {DetermineValueText(first.Value)}";
        }
    }

    private sealed class ValueEditorRow
    {
        public string Key { get; set; } = string.Empty;
        public string ValueType { get; set; } = "String";
        public string ValueText { get; set; } = string.Empty;

        public bool IsBlank() => string.IsNullOrWhiteSpace(Key) && string.IsNullOrWhiteSpace(ValueText);
    }

    private sealed class LookupEditorRow
    {
        public string TargetColumn { get; set; } = string.Empty;
        public string LookupTable { get; set; } = string.Empty;
        public string LookupColumn { get; set; } = string.Empty;
        public string SelectColumn { get; set; } = "id";
        public string LookupValueType { get; set; } = "String";
        public string LookupValueText { get; set; } = string.Empty;
        public bool Required { get; set; } = true;

        public bool IsBlank()
        {
            return string.IsNullOrWhiteSpace(TargetColumn) &&
                   string.IsNullOrWhiteSpace(LookupTable) &&
                   string.IsNullOrWhiteSpace(LookupColumn) &&
                   string.IsNullOrWhiteSpace(LookupValueText);
        }
    }
}
