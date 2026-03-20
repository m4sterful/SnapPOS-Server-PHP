using System.Windows.Forms;

namespace DotPos.SchemaEditor;

internal sealed class ColumnMultiSelectDialog : Form
{
    private readonly CheckedListBox _columnsList = new() { Dock = DockStyle.Fill, CheckOnClick = true };
    private readonly int? _requiredCount;
    private readonly Label _promptLabel = new() { AutoSize = true };

    private ColumnMultiSelectDialog(
        string title,
        IReadOnlyList<string> availableColumns,
        IReadOnlyCollection<string> selectedColumns,
        string prompt,
        int? requiredCount)
    {
        _requiredCount = requiredCount;
        Text = title;
        Width = 420;
        Height = 520;
        StartPosition = FormStartPosition.CenterParent;
        FormBorderStyle = FormBorderStyle.FixedDialog;
        MaximizeBox = false;
        MinimizeBox = false;

        var layout = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 3,
            Padding = new Padding(10)
        };
        layout.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        layout.RowStyles.Add(new RowStyle(SizeType.Percent, 100));
        layout.RowStyles.Add(new RowStyle(SizeType.AutoSize));

        _promptLabel.Text = prompt;
        layout.Controls.Add(_promptLabel, 0, 0);

        foreach (var column in availableColumns)
        {
            _columnsList.Items.Add(column, selectedColumns.Contains(column, StringComparer.OrdinalIgnoreCase));
        }

        var buttonPanel = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            FlowDirection = FlowDirection.RightToLeft,
            AutoSize = true
        };

        var okButton = new Button { Text = "OK", AutoSize = true };
        okButton.Click += (_, e) =>
        {
            if (_requiredCount.HasValue && _columnsList.CheckedItems.Count != _requiredCount.Value)
            {
                MessageBox.Show(
                    this,
                    $"Select exactly {_requiredCount.Value} column{(_requiredCount.Value == 1 ? string.Empty : "s")}.",
                    "Column Selection",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Warning);
                return;
            }

            DialogResult = DialogResult.OK;
            Close();
        };
        var cancelButton = new Button { Text = "Cancel", AutoSize = true, DialogResult = DialogResult.Cancel };
        buttonPanel.Controls.Add(okButton);
        buttonPanel.Controls.Add(cancelButton);

        AcceptButton = okButton;
        CancelButton = cancelButton;

        layout.Controls.Add(_columnsList, 0, 1);
        layout.Controls.Add(buttonPanel, 0, 2);
        Controls.Add(layout);
    }

    public static string? SelectColumns(
        IWin32Window owner,
        string title,
        IReadOnlyList<string> availableColumns,
        IReadOnlyCollection<string> selectedColumns,
        string prompt = "Select one or more columns in index order.",
        int? requiredCount = null)
    {
        using var dialog = new ColumnMultiSelectDialog(title, availableColumns, selectedColumns, prompt, requiredCount);
        return dialog.ShowDialog(owner) == DialogResult.OK
            ? string.Join(", ", dialog._columnsList.CheckedItems.Cast<string>())
            : null;
    }
}
