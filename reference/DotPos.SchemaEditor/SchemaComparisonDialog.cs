using System.ComponentModel;
using DotPos.Shared.LocalDatabase;
using DotPos.Shared.LocalDatabase.Schema;
using System.Windows.Forms;

namespace DotPos.SchemaEditor;

internal sealed class SchemaComparisonDialog : Form
{
    private readonly string _schemaRoot;
    private readonly LocalDbConnectionSettings _settings;
    private readonly Action<LocalDatabaseApplyMessage> _report;
    private readonly DataGridView _differencesGrid = new() { Dock = DockStyle.Fill, AutoGenerateColumns = false };
    private readonly Label _summaryLabel = new() { Dock = DockStyle.Fill, AutoEllipsis = true };
    private BindingList<SchemaDifferenceRow> _rows = [];

    public SchemaComparisonDialog(
        string schemaRoot,
        LocalDbConnectionSettings settings,
        LocalDatabaseSchemaComparisonReport report,
        Action<LocalDatabaseApplyMessage> reportCallback)
    {
        _schemaRoot = schemaRoot;
        _settings = new LocalDbConnectionSettings
        {
            Host = settings.Host,
            Port = settings.Port,
            DatabaseName = settings.DatabaseName,
            Username = settings.Username,
            Password = settings.Password
        };
        _report = reportCallback;

        Text = $"DB vs Schema: {report.DatabaseName}";
        Width = 1100;
        Height = 560;
        StartPosition = FormStartPosition.CenterParent;

        BuildLayout();
        LoadReport(report);
    }

    private void BuildLayout()
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

        ConfigureGrid();

        var buttonPanel = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            AutoSize = true
        };

        var resolveButton = new Button { Text = "Resolve Selected To Schema", AutoSize = true };
        resolveButton.Click += async (_, _) => await ResolveSelectedAsync();

        var refreshButton = new Button { Text = "Refresh", AutoSize = true };
        refreshButton.Click += async (_, _) => await RefreshReportAsync();

        var closeButton = new Button { Text = "Close", AutoSize = true };
        closeButton.Click += (_, _) => Close();

        buttonPanel.Controls.Add(resolveButton);
        buttonPanel.Controls.Add(refreshButton);
        buttonPanel.Controls.Add(closeButton);

        panel.Controls.Add(_summaryLabel, 0, 0);
        panel.Controls.Add(_differencesGrid, 0, 1);
        panel.Controls.Add(buttonPanel, 0, 2);
        Controls.Add(panel);
    }

    private void ConfigureGrid()
    {
        _differencesGrid.AllowUserToAddRows = false;
        _differencesGrid.AllowUserToDeleteRows = false;
        _differencesGrid.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
        _differencesGrid.MultiSelect = true;
        _differencesGrid.Columns.Add(new DataGridViewCheckBoxColumn
        {
            HeaderText = "Resolve",
            DataPropertyName = nameof(SchemaDifferenceRow.Selected),
            Width = 65
        });
        _differencesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Table",
            DataPropertyName = nameof(SchemaDifferenceRow.TableName),
            Width = 140
        });
        _differencesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Kind",
            DataPropertyName = nameof(SchemaDifferenceRow.Kind),
            Width = 170
        });
        _differencesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Subject",
            DataPropertyName = nameof(SchemaDifferenceRow.SubjectName),
            Width = 180
        });
        _differencesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Schema",
            DataPropertyName = nameof(SchemaDifferenceRow.SchemaValue),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 26
        });
        _differencesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Database",
            DataPropertyName = nameof(SchemaDifferenceRow.DatabaseValue),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 26
        });
        _differencesGrid.Columns.Add(new DataGridViewTextBoxColumn
        {
            HeaderText = "Details",
            DataPropertyName = nameof(SchemaDifferenceRow.Message),
            AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill,
            FillWeight = 48
        });
    }

    private void LoadReport(LocalDatabaseSchemaComparisonReport report)
    {
        _rows = new BindingList<SchemaDifferenceRow>(
            report.Differences
                .OrderBy(difference => difference.TableName, StringComparer.OrdinalIgnoreCase)
                .ThenBy(difference => difference.SubjectName, StringComparer.OrdinalIgnoreCase)
                .Select(difference => new SchemaDifferenceRow
                {
                    Difference = difference,
                    Selected = false,
                    TableName = difference.TableName,
                    Kind = difference.Kind.ToString(),
                    SubjectName = difference.SubjectName,
                    SchemaValue = difference.SchemaValue,
                    DatabaseValue = difference.DatabaseValue,
                    Message = difference.Message
                })
                .ToList());

        _differencesGrid.DataSource = _rows;
        var resolvableCount = _rows.Count(row => row.Difference.CanResolveToSchema);
        _summaryLabel.Text = _rows.Count == 0
            ? $"No differences found for {_settings.DisplayName}."
            : $"Found {_rows.Count} difference(s) for {_settings.DisplayName}. Resolvable: {resolvableCount}.";
    }

    private async Task ResolveSelectedAsync()
    {
        var selected = _rows
            .Where(row => row.Selected && row.Difference.CanResolveToSchema)
            .Select(row => row.Difference)
            .ToList();
        if (selected.Count == 0)
        {
            MessageBox.Show(this, "Select one or more resolvable differences first.", "Compare DB To Schema", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            return;
        }

        Enabled = false;
        UseWaitCursor = true;
        try
        {
            var applier = new LocalDatabaseSchemaApplier(_report);
            await applier.ResolveDifferencesAsync(_settings, selected, CancellationToken.None);
            await RefreshReportAsync();
            MessageBox.Show(this, "Selected differences were resolved to match the schema.", "Compare DB To Schema", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }
        catch (Exception ex)
        {
            MessageBox.Show(this, ex.Message, "Compare DB To Schema", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
        finally
        {
            UseWaitCursor = false;
            Enabled = true;
        }
    }

    private async Task RefreshReportAsync()
    {
        Enabled = false;
        UseWaitCursor = true;
        try
        {
            var applier = new LocalDatabaseSchemaApplier(_report);
            var report = await applier.CompareAsync(_schemaRoot, _settings, CancellationToken.None);
            LoadReport(report);
        }
        catch (Exception ex)
        {
            MessageBox.Show(this, ex.Message, "Compare DB To Schema", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
        finally
        {
            UseWaitCursor = false;
            Enabled = true;
        }
    }

    private sealed class SchemaDifferenceRow
    {
        public required LocalDatabaseSchemaDifference Difference { get; init; }
        public bool Selected { get; set; }
        public string TableName { get; init; } = string.Empty;
        public string Kind { get; init; } = string.Empty;
        public string SubjectName { get; init; } = string.Empty;
        public string SchemaValue { get; init; } = string.Empty;
        public string DatabaseValue { get; init; } = string.Empty;
        public string Message { get; init; } = string.Empty;
    }
}
